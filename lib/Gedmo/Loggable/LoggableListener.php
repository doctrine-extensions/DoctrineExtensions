<?php

namespace Gedmo\Loggable;

use Doctrine\Common\EventArgs;
use Gedmo\Mapping\MappedEventSubscriber;
use Gedmo\Loggable\Mapping\Event\LoggableAdapter;
use Gedmo\Mapping\ObjectManagerHelper as OMH;
use Gedmo\Exception\InvalidArgumentException;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;

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
            throw new InvalidArgumentException("Username must be a string, or object should have method: getUsername");
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
     * Mapps additional metadata
     *
     * @param EventArgs $event
     */
    public function loadClassMetadata(EventArgs $event)
    {
        $this->loadMetadataForObjectClass(OMH::getObjectManagerFromEvent($event), $event->getClassMetadata());
    }

    /**
     * Checks for inserted object to update its logEntry
     * foreign key
     *
     * @param EventArgs $event
     */
    public function postPersist(EventArgs $event)
    {
        $object = OMH::getObjectFromEvent($event);
        $om = OMH::getObjectManagerFromEvent($event);
        $oid = spl_object_hash($object);
        $uow = $om->getUnitOfWork();
        if ($this->pendingLogEntryInserts && array_key_exists($oid, $this->pendingLogEntryInserts)) {
            $logEntry = $this->pendingLogEntryInserts[$oid];
            $logEntryMeta = $om->getClassMetadata(get_class($logEntry));

            $id = OMH::getIdentifier($om, $object);
            $logEntryMeta->getReflectionProperty('objectId')->setValue($logEntry, $id);
            $uow->scheduleExtraUpdate($logEntry, array(
                'objectId' => array(null, $id)
            ));
            OMH::setOriginalObjectProperty($uow, spl_object_hash($logEntry), 'objectId', $id);
            unset($this->pendingLogEntryInserts[$oid]);
        }
        if ($this->pendingRelatedObjects && array_key_exists($oid, $this->pendingRelatedObjects)) {
            // document manager should fetch single identifier, it cannot find document by array
            $identifiers = OMH::getIdentifier($om, $object, $om instanceof DocumentManager);
            foreach ($this->pendingRelatedObjects[$oid] as $props) {
                $logEntry = $props['log'];
                $logEntryMeta = $om->getClassMetadata(get_class($logEntry));
                $oldData = $data = $logEntry->getData();
                $data[$props['field']] = $identifiers;
                $logEntry->setData($data);

                $uow->scheduleExtraUpdate($logEntry, array(
                    'data' => array($oldData, $data)
                ));
                OMH::setOriginalObjectProperty($uow, spl_object_hash($logEntry), 'data', $data);
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
     * @param EventArgs $event
     */
    public function onFlush(EventArgs $event)
    {
        $om = OMH::getObjectManagerFromEvent($event);
        $uow = $om->getUnitOfWork();

        foreach (OMH::getScheduledObjectInsertions($uow) as $object) {
            $this->createLogEntry(self::ACTION_CREATE, $object, $om);
        }
        foreach (OMH::getScheduledObjectUpdates($uow) as $object) {
            $this->createLogEntry(self::ACTION_UPDATE, $object, $om);
        }
        foreach (OMH::getScheduledObjectDeletions($uow) as $object) {
            $this->createLogEntry(self::ACTION_REMOVE, $object, $om);
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
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     */
    protected function createLogEntry($action, $object, ObjectManager $om)
    {
        $meta = $om->getClassMetadata(get_class($object));
        if ($exm = $this->getConfiguration($om, $meta->name)) {
            $logEntryClass = $exm->getLogClass();
            $logEntryMeta = $om->getClassMetadata($logEntryClass);
            /** @var \Gedmo\Loggable\Entity\LogEntry $logEntry */
            $logEntry = $logEntryMeta->newInstance();

            $logEntry->setAction($action);
            $logEntry->setUsername($this->username);
            $logEntry->setObjectClass(OMH::getRootObjectClass($meta));
            $logEntry->setLoggedAt();

            // check for the availability of the primary key
            $objectId = OMH::getIdentifier($om, $object);
            if (!$objectId && $action === self::ACTION_CREATE) {
                $this->pendingLogEntryInserts[spl_object_hash($object)] = $logEntry;
            }
            $uow = $om->getUnitOfWork();
            $logEntry->setObjectId($objectId);
            $newValues = array();
            $versionedFields = $exm->getVersionedFields();
            if ($action !== self::ACTION_REMOVE) {
                foreach (OMH::getObjectChangeSet($uow, $object) as $field => $changes) {
                    if (!in_array($field, $versionedFields)) {
                        continue;
                    }
                    $value = $changes[1];
                    if ($meta->isSingleValuedAssociation($field) && $value) {
                        $oid = spl_object_hash($value);
                        $value = OMH::getIdentifier($om, $value, false);
                        if (!$value) {
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

            if ($action === self::ACTION_UPDATE && 0 === count($newValues)) {
                return;
            }

            $version = 1;
            if ($action !== self::ACTION_CREATE) {
                $version = $this->getNewVersion($om, $logEntryMeta, $object);
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

    /**
     * Get the next LogEntry version
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $logMeta
     * @param object $object
     * @return integer
     */
    protected function getNewVersion(ObjectManager $om, ClassMetadata $logMeta, $object)
    {
        $objectMeta = $om->getClassMetadata(get_class($object));
        $objectClass = OMH::getRootObjectClass($objectMeta);
        $id = OMH::getIdentifier($om, $object);
        $num = 1;

        if ($om instanceof EntityManager) {
            $dql = "SELECT MAX(log.version) FROM {$logMeta->name} log";
            $dql .= " WHERE log.objectId = :id";
            $dql .= " AND log.objectClass = :objectClass";

            $num = $om
                ->createQuery($dql)
                ->setParameters(compact('id', 'objectClass'))
                ->getSingleScalarResult() + 1;
        } else {
            $q = $om
                ->createQueryBuilder($logMeta->name)
                ->select('version')
                ->field('objectId')->equals($id)
                ->field('objectClass')->equals($objectClass)
                ->sort('version', 'DESC')
                ->limit(1)
                ->getQuery();

            $q->setHydrate(false);
            if ($result = $q->getSingleResult()) {
                $num = $result['version'] + 1;
            }
        }
        return $num;
    }
}
