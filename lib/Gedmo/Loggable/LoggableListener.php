<?php

namespace Gedmo\Loggable;

use Doctrine\Common\EventArgs;
use Gedmo\Mapping\MappedEventSubscriber;
use Gedmo\Loggable\Mapping\Event\LoggableAdapter;
use Gedmo\Tool\Wrapper\AbstractWrapper;

/**
 * Loggable listener
 *
 * @author Boussekeyt Jules <jules.boussekeyt@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class LoggableListener extends MappedEventSubscriber
{
    /**
     * Create action
     */
    const ACTION_CREATE = 'create';

    /**
     * Update action
     */
    const ACTION_UPDATE = 'update';

    /**
     * Remove action
     */
    const ACTION_REMOVE = 'remove';

    /**
     * Username for identification
     *
     * @var string
     */
    protected $username;

    /**
     * List of log entries which do not have the foreign
     * key generated yet - MySQL case. These entries
     * will be updated with new keys on postPersist event
     *
     * @var array
     */
    protected $pendingLogEntryInserts = array();

    /**
     * For log of changed relations we use
     * its identifiers to avoid storing serialized Proxies.
     * These are pending relations in case it does not
     * have an identifier yet
     *
     * @var array
     */
    protected $pendingRelatedObjects = array();

    /**
     * Set username for identification
     *
     * @param mixed $username
     * @throws \Gedmo\Exception\InvalidArgumentException Invalid username
     */
    public function setUsername($username)
    {
        if (is_string($username)) {
            $this->username = $username;
        } elseif (is_object($username) && method_exists($username, 'getUsername')) {
            $this->username = (string)$username->getUsername();
        } else {
            throw new \Gedmo\Exception\InvalidArgumentException("Username must be a string, or object should have method: getUsername");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return array(
            'onFlush',
            'loadClassMetadata',
            'postPersist'
        );
    }

    /**
     * Get the LogEntry class
     *
     * @param LoggableAdapter $ea
     * @param string $class
     * @return string
     */
    protected function getLogEntryClass(LoggableAdapter $ea, $class)
    {
        return isset(self::$configurations[$this->name][$class]['logEntryClass']) ?
            self::$configurations[$this->name][$class]['logEntryClass'] :
            $ea->getDefaultLogEntryClass();
    }

    /**
     * Mapps additional metadata
     *
     * @param EventArgs $eventArgs
     * @return void
     */
    public function loadClassMetadata(EventArgs $eventArgs)
    {
        $ea = $this->getEventAdapter($eventArgs);
        $this->loadMetadataForObjectClass($ea->getObjectManager(), $eventArgs->getClassMetadata());
    }

    /**
     * Checks for inserted object to update its logEntry
     * foreign key
     *
     * @param EventArgs $args
     * @return void
     */
    public function postPersist(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $object = $ea->getObject();
        $om = $ea->getObjectManager();
        $oid = spl_object_hash($object);
        $uow = $om->getUnitOfWork();
        if ($this->pendingLogEntryInserts && array_key_exists($oid, $this->pendingLogEntryInserts)) {
            $wrapped = AbstractWrapper::wrap($object, $om);

            $logEntry = $this->pendingLogEntryInserts[$oid];
            $logEntryMeta = $om->getClassMetadata(get_class($logEntry));

            $id = $wrapped->getIdentifier();
            $logEntryMeta->getReflectionProperty('objectId')->setValue($logEntry, $id);
            $uow->scheduleExtraUpdate($logEntry, array(
                'objectId' => array(null, $id)
            ));
            $ea->setOriginalObjectProperty($uow, spl_object_hash($logEntry), 'objectId', $id);
            unset($this->pendingLogEntryInserts[$oid]);
        }
        if ($this->pendingRelatedObjects && array_key_exists($oid, $this->pendingRelatedObjects)) {
            $wrapped = AbstractWrapper::wrap($object, $om);
            $identifiers = $wrapped->getIdentifier(false);
            foreach ($this->pendingRelatedObjects[$oid] as $props) {
                $logEntry = $props['log'];
                $logEntryMeta = $om->getClassMetadata(get_class($logEntry));
                $oldData = $data = $logEntry->getData();
                $data[$props['field']] = $identifiers;
                $logEntry->setData($data);

                $uow->scheduleExtraUpdate($logEntry, array(
                    'data' => array($oldData, $data)
                ));
                $ea->setOriginalObjectProperty($uow, spl_object_hash($logEntry), 'data', $data);
            }
            unset($this->pendingRelatedObjects[$oid]);
        }
    }

    /**
     * Handle any custom LogEntry functionality that needs to be performed
     * before persisting it
     *
     * @param object $logEntry The LogEntry being persisted
     * @param object $object   The object being Logged
     */
    protected function prePersistLogEntry($logEntry, $object)
    {

    }

    /**
     * Looks for loggable objects being inserted or updated
     * for further processing
     *
     * @param EventArgs $eventArgs
     * @return void
     */
    public function onFlush(EventArgs $eventArgs)
    {
        $ea = $this->getEventAdapter($eventArgs);
        $om = $ea->getObjectManager();
        $uow = $om->getUnitOfWork();

        foreach ($ea->getScheduledObjectInsertions($uow) as $object) {
            $this->createLogEntry(self::ACTION_CREATE, $object, $ea);
        }
        foreach ($ea->getScheduledObjectUpdates($uow) as $object) {
            $this->createLogEntry(self::ACTION_UPDATE, $object, $ea);
        }
        foreach ($ea->getScheduledObjectDeletions($uow) as $object) {
            $this->createLogEntry(self::ACTION_REMOVE, $object, $ea);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function getNamespace()
    {
        return __NAMESPACE__;
    }

    /**
     * Create a new Log instance
     *
     * @param string $action
     * @param object $object
     * @param LoggableAdapter $ea
     * @return void
     */
    protected function createLogEntry($action, $object, LoggableAdapter $ea)
    {
        $om = $ea->getObjectManager();
        $wrapped = AbstractWrapper::wrap($object, $om);
        $meta = $wrapped->getMetadata();
        if ($config = $this->getConfiguration($om, $meta->name)) {
            $logEntryClass = $this->getLogEntryClass($ea, $meta->name);
            $logEntryMeta = $om->getClassMetadata($logEntryClass);
            /** @var \Gedmo\Loggable\Entity\LogEntry $logEntry */
            $logEntry = $logEntryMeta->newInstance();

            $logEntry->setAction($action);
            $logEntry->setUsername($this->username);
            $logEntry->setObjectClass($meta->name);
            $logEntry->setLoggedAt();

            // check for the availability of the primary key
            $uow = $om->getUnitOfWork();
            if ($action === self::ACTION_CREATE && $ea->isPostInsertGenerator($meta)) {
                $this->pendingLogEntryInserts[spl_object_hash($object)] = $logEntry;
            } else {
                $logEntry->setObjectId($wrapped->getIdentifier());
            }
            $newValues = array();
            if ($action !== self::ACTION_REMOVE && isset($config['versioned'])) {
                foreach ($ea->getObjectChangeSet($uow, $object) as $field => $changes) {
                    if (!in_array($field, $config['versioned'])) {
                        continue;
                    }
                    $value = $changes[1];
                    if ($meta->isSingleValuedAssociation($field) && $value) {
                        $oid = spl_object_hash($value);
                        $wrappedAssoc = AbstractWrapper::wrap($value, $om);
                        $value = $wrappedAssoc->getIdentifier(false);
                        if (!is_array($value) && !$value) {
                            $this->pendingRelatedObjects[$oid][] = array(
                                'log' => $logEntry,
                                'field' => $field
                            );
                        }
                    }
                    $newValues[$field] = $value;
                }
                $logEntry->setData($newValues);
            }

            if($action === self::ACTION_UPDATE && 0 === count($newValues)) {
                return;
            }

            $version = 1;
            if ($action !== self::ACTION_CREATE) {
                $version = $ea->getNewVersion($logEntryMeta, $object);
                if (empty($version)) {
                    // was versioned later
                    $version = 1;
                }
            }
            $logEntry->setVersion($version);

            $this->prePersistLogEntry($logEntry, $object);

            $om->persist($logEntry);
            $uow->computeChangeSet($logEntryMeta, $logEntry);
        }
    }
}
