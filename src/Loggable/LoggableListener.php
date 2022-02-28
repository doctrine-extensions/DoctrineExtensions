<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Loggable;

use Doctrine\Common\EventArgs;
use Doctrine\Persistence\Event\LoadClassMetadataEventArgs;
use Gedmo\Loggable\Mapping\Event\LoggableAdapter;
use Gedmo\Mapping\MappedEventSubscriber;
use Gedmo\Tool\Wrapper\AbstractWrapper;

/**
 * Loggable listener
 *
 * @author Boussekeyt Jules <jules.boussekeyt@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
class LoggableListener extends MappedEventSubscriber
{
    /**
     * Create action
     */
    public const ACTION_CREATE = 'create';

    /**
     * Update action
     */
    public const ACTION_UPDATE = 'update';

    /**
     * Remove action
     */
    public const ACTION_REMOVE = 'remove';

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
    protected $pendingLogEntryInserts = [];

    /**
     * For log of changed relations we use
     * its identifiers to avoid storing serialized Proxies.
     * These are pending relations in case it does not
     * have an identifier yet
     *
     * @var array
     */
    protected $pendingRelatedObjects = [];

    /**
     * Set username for identification
     *
     * @param mixed $username
     *
     * @throws \Gedmo\Exception\InvalidArgumentException Invalid username
     *
     * @return void
     */
    public function setUsername($username)
    {
        if (is_string($username)) {
            $this->username = $username;
        } elseif (is_object($username) && method_exists($username, 'getUserIdentifier')) {
            $this->username = (string) $username->getUserIdentifier();
        } elseif (is_object($username) && method_exists($username, 'getUsername')) {
            $this->username = (string) $username->getUsername();
        } elseif (is_object($username) && method_exists($username, '__toString')) {
            $this->username = $username->__toString();
        } else {
            throw new \Gedmo\Exception\InvalidArgumentException('Username must be a string, or object should have method getUserIdentifier, getUsername or __toString');
        }
    }

    /**
     * @return string[]
     */
    public function getSubscribedEvents()
    {
        return [
            'onFlush',
            'loadClassMetadata',
            'postPersist',
        ];
    }

    /**
     * Maps additional metadata
     *
     * @param LoadClassMetadataEventArgs $eventArgs
     *
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
     * @return void
     */
    public function postPersist(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $object = $ea->getObject();
        $om = $ea->getObjectManager();
        $oid = spl_object_id($object);
        $uow = $om->getUnitOfWork();
        if ($this->pendingLogEntryInserts && array_key_exists($oid, $this->pendingLogEntryInserts)) {
            $wrapped = AbstractWrapper::wrap($object, $om);

            $logEntry = $this->pendingLogEntryInserts[$oid];
            $logEntryMeta = $om->getClassMetadata(get_class($logEntry));

            $id = $wrapped->getIdentifier();
            $logEntryMeta->getReflectionProperty('objectId')->setValue($logEntry, $id);
            $uow->scheduleExtraUpdate($logEntry, [
                'objectId' => [null, $id],
            ]);
            $ea->setOriginalObjectProperty($uow, $logEntry, 'objectId', $id);
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

                $uow->scheduleExtraUpdate($logEntry, [
                    'data' => [$oldData, $data],
                ]);
                $ea->setOriginalObjectProperty($uow, $logEntry, 'data', $data);
            }
            unset($this->pendingRelatedObjects[$oid]);
        }
    }

    /**
     * Looks for loggable objects being inserted or updated
     * for further processing
     *
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
     * Get the LogEntry class
     *
     * @param string $class
     *
     * @return string
     */
    protected function getLogEntryClass(LoggableAdapter $ea, $class)
    {
        return self::$configurations[$this->name][$class]['logEntryClass'] ?? $ea->getDefaultLogEntryClass();
    }

    /**
     * Handle any custom LogEntry functionality that needs to be performed
     * before persisting it
     *
     * @param object $logEntry The LogEntry being persisted
     * @param object $object   The object being Logged
     *
     * @return void
     */
    protected function prePersistLogEntry($logEntry, $object)
    {
    }

    protected function getNamespace()
    {
        return __NAMESPACE__;
    }

    /**
     * Returns an objects changeset data
     *
     * @param LoggableAdapter $ea
     * @param object          $object
     * @param object          $logEntry
     *
     * @return array
     */
    protected function getObjectChangeSetData($ea, $object, $logEntry)
    {
        $om = $ea->getObjectManager();
        $wrapped = AbstractWrapper::wrap($object, $om);
        $meta = $wrapped->getMetadata();
        $config = $this->getConfiguration($om, $meta->getName());
        $uow = $om->getUnitOfWork();
        $newValues = [];

        foreach ($ea->getObjectChangeSet($uow, $object) as $field => $changes) {
            if (empty($config['versioned']) || !in_array($field, $config['versioned'], true)) {
                continue;
            }
            $value = $changes[1];
            if ($meta->isSingleValuedAssociation($field) && $value) {
                if ($wrapped->isEmbeddedAssociation($field)) {
                    $value = $this->getObjectChangeSetData($ea, $value, $logEntry);
                } else {
                    $oid = spl_object_id($value);
                    $wrappedAssoc = AbstractWrapper::wrap($value, $om);
                    $value = $wrappedAssoc->getIdentifier(false);
                    if (!is_array($value) && !$value) {
                        $this->pendingRelatedObjects[$oid][] = [
                            'log' => $logEntry,
                            'field' => $field,
                        ];
                    }
                }
            }
            $newValues[$field] = $value;
        }

        return $newValues;
    }

    /**
     * Create a new Log instance
     *
     * @param string $action
     * @param object $object
     *
     * @return \Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry|null
     */
    protected function createLogEntry($action, $object, LoggableAdapter $ea)
    {
        $om = $ea->getObjectManager();
        $wrapped = AbstractWrapper::wrap($object, $om);
        $meta = $wrapped->getMetadata();

        // Filter embedded documents
        if (isset($meta->isEmbeddedDocument) && $meta->isEmbeddedDocument) {
            return null;
        }

        if ($config = $this->getConfiguration($om, $meta->getName())) {
            $logEntryClass = $this->getLogEntryClass($ea, $meta->getName());
            $logEntryMeta = $om->getClassMetadata($logEntryClass);
            /** @var \Gedmo\Loggable\Entity\LogEntry $logEntry */
            $logEntry = $logEntryMeta->newInstance();

            $logEntry->setAction($action);
            $logEntry->setUsername($this->username);
            $logEntry->setObjectClass($meta->getName());
            $logEntry->setLoggedAt();

            // check for the availability of the primary key
            $uow = $om->getUnitOfWork();
            if (self::ACTION_CREATE === $action && $ea->isPostInsertGenerator($meta)) {
                $this->pendingLogEntryInserts[spl_object_id($object)] = $logEntry;
            } else {
                $logEntry->setObjectId($wrapped->getIdentifier());
            }
            $newValues = [];
            if (self::ACTION_REMOVE !== $action && isset($config['versioned'])) {
                $newValues = $this->getObjectChangeSetData($ea, $object, $logEntry);
                $logEntry->setData($newValues);
            }

            if (self::ACTION_UPDATE === $action && 0 === count($newValues)) {
                return null;
            }

            $version = 1;
            if (self::ACTION_CREATE !== $action) {
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

            return $logEntry;
        }

        return null;
    }
}
