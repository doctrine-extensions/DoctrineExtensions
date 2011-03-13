<?php

namespace Gedmo\Loggable;

use Doctrine\Common\EventArgs,
    Doctrine\Common\Persistence\ObjectManager,
    Doctrine\Common\Persistence\Mapping\ClassMetadata,
    Gedmo\Mapping\MappedEventSubscriber;

/**
 * The AbstractLoggableListener is an abstract class
 * of loggable listener in order to support diferent
 * object managers.
 *
 * @author Boussekeyt Jules <jules.boussekeyt@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Loggable
 * @subpackage AbstractLoggableListener
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class AbstractLoggableListener extends MappedEventSubscriber
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
    private $pendingLogEntryInserts = array();
    
    /**
     * For log of changed relations we use
     * its identifiers to avoid storing serialized Proxies.
     * These are pending relations in case it does not
     * have an identifier yet
     * 
     * @var array
     */
    private $pendingRelatedObjects = array();
    
    /**
     * Set username for identification
     *
     * @param mixed $username
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
     * Mapps additional metadata
     *
     * @param EventArgs $eventArgs
     * @return void
     */
    public function loadClassMetadata(EventArgs $eventArgs)
    {
        $this->loadMetadataForObjectClass($this->getObjectManager($eventArgs), $eventArgs->getClassMetadata());
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
        $object = $this->getObject($args);
        $om = $this->getObjectManager($args);
        $oid = spl_object_hash($object);
        $uow = $om->getUnitOfWork();
        if ($this->pendingLogEntryInserts && array_key_exists($oid, $this->pendingLogEntryInserts)) {            
            $meta = $om->getClassMetadata(get_class($object));
            $config = $this->getConfiguration($om, $meta->name);
            // there should be single identifier
            $identifierField = $this->getSingleIdentifierFieldName($meta);
            $logEntry = $this->pendingLogEntryInserts[$oid];
            $logEntryMeta = $om->getClassMetadata(get_class($logEntry));
            
            $id = $meta->getReflectionProperty($identifierField)->getValue($object);
            $logEntryMeta->getReflectionProperty('objectId')->setValue($logEntry, $id);
            $uow->scheduleExtraUpdate($logEntry, array(
                'objectId' => array(null, $id)
            ));
            unset($this->pendingLogEntryInserts[$oid]);
        }
        if ($this->pendingRelatedObjects && array_key_exists($oid, $this->pendingRelatedObjects)) {
            $identifiers = $this->extractIdentifiers($om, $object);
            $props = $this->pendingRelatedObjects[$oid];
            
            $logEntry = $props['log'];
            $logEntryMeta = $om->getClassMetadata(get_class($logEntry));
            $serialized = $logEntryMeta->getReflectionProperty('data')->getValue($logEntry);
            $data = $logEntry->getData();
            $data[$props['field']] = $identifiers;
            $logEntry->setData($data);
            
            $uow->scheduleExtraUpdate($logEntry, array(
                'data' => array($serialized, serialize($data))
            ));
            unset($this->pendingRelatedObjects[$oid]);
        }
    }
    
    /**
     * Looks for loggable objects being inserted or updated
     * for further processing
     *
     * @param EventArgs $args
     * @return void
     */
    public function onFlush(EventArgs $eventArgs)
    {
        $om = $this->getObjectManager($eventArgs);
        $uow = $om->getUnitOfWork();

        foreach ($this->getScheduledObjectInsertions($uow) as $object) {
            $this->createLogEntry(self::ACTION_CREATE, $object, $om);
        }
        foreach ($this->getScheduledObjectUpdates($uow) as $object) {
            $this->createLogEntry(self::ACTION_UPDATE, $object, $om);
        }
        foreach ($this->getScheduledObjectDeletions($uow) as $object) {
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
     * Check if LogEntry object is not instance of
     * LogEntry
     * 
     * @param object $logEntry
     * @return bool
     */
    abstract protected function isTransient($logEntry);
    
    /**
     * Get the LogEntry class
     *
     * @param array $config
     * @param string $class
     * @return string
     */
    abstract protected function getLogEntryClass(array $config, $class);

    /**
     * Get the ObjectManager from EventArgs
     *
     * @param EventArgs $args
     * @return ObjectManager
     */
    abstract protected function getObjectManager(EventArgs $args);

    /**
     * Get the scheduled object updates from a UnitOfWork
     *
     * @param UnitOfWork $uow
     * @return array
     */
    abstract protected function getScheduledObjectUpdates($uow);

    /**
     * Get the scheduled object insertions from a UnitOfWork
     *
     * @param UnitOfWork $uow
     * @return array
     */
    abstract protected function getScheduledObjectInsertions($uow);

    /**
     * Get the scheduled object deletions from a UnitOfWork
     *
     * @param UnitOfWork $uow
     * @return array
     */
    abstract protected function getScheduledObjectDeletions($uow);
    
    /**
     * Get the object changeset from a UnitOfWork
     *
     * @param UnitOfWork $uow
     * @param Object $object
     * @return array
     */
    abstract protected function getObjectChangeSet($uow, $object);
    
    /**
     * Get the single identifier field name
     *
     * @param ClassMetadata $meta
     * @throws MappingException - if identifier is composite
     * @return string
     */
    abstract protected function getSingleIdentifierFieldName(ClassMetadata $meta);
    
    /**
     * Get the Object from EventArgs
     *
     * @param EventArgs $args
     * @return object
     */
    abstract protected function getObject(EventArgs $args);
    
    /**
     * Get the Object from EventArgs
     *
     * @param ClassMetadata $meta
     * @param ObjectManager $om
     * @param object $object
     * @return integer
     */
    abstract protected function getNewVersion(ClassMetadata $meta, ObjectManager $om, $object);
    
    /**
     * Extracts all identifiers from the object,
     * if there are no identifiers yet, returns
     * object hash to schedule extra update
     *
     * @param ObjectManager $om
     * @param object $object
     * @return array
     */
    private function extractIdentifiers(ObjectManager $om, $object)
    {
        $meta = $om->getClassMetadata(get_class($object));
        $identifier = array();
        foreach ((array)$meta->identifier as $field) {
            $id = $meta->getReflectionProperty($field)->getValue($object);
            if (!$id) {
                return spl_object_hash($object);
            }
            $identifier[$field] = $id;
        }
        return $identifier;
    }
    
    /**
     * Create a new Log instance
     *
     * @param string $action
     * @param object $object
     * @param ObjectManager $om
     * @return void
     */
    private function createLogEntry($action, $object, ObjectManager $om)
    {
        $meta = $om->getClassMetadata(get_class($object));
        if ($config = $this->getConfiguration($om, $meta->name)) {
            $logEntryClass = $this->getLogEntryClass($config, $meta->name);
            $logEntry = new $logEntryClass;
            
            if ($this->isTransient($logEntry)) {
                throw new \Gedmo\Exception\InvalidMappingException('LogEntry class: ' . get_class($logEntry) . ' must extend AbstractLogEntry mappedsuperclass');
            }
            
            $logEntry->setAction($action);
            $logEntry->setUsername($this->username);
            $logEntry->setObjectClass($meta->name);
            $logEntry->setLoggedAt();
            
            // check for the availability of the primary key
            $identifierField = $this->getSingleIdentifierFieldName($meta);
            $objectId = $meta->getReflectionProperty($identifierField)->getValue($object);
            if (!$objectId && $action === self::ACTION_CREATE) {
                $this->pendingLogEntryInserts[spl_object_hash($object)] = $logEntry;
            }
            $uow = $om->getUnitOfWork();
            $logEntry->setObjectId($objectId);
            if ($action !== self::ACTION_REMOVE) {
                $newValues = array();
                foreach ($this->getObjectChangeSet($uow, $object) as $field => $changes) {
                    $value = $changes[1];
                    if ($meta->isCollectionValuedAssociation($field)) {
                        continue;
                    }
                    if ($meta->isSingleValuedAssociation($field) && $value) {
                        $value = $this->extractIdentifiers($om, $value);
                        if (!is_array($value)) {
                            $this->pendingRelatedObjects[$value] = array(
                                'log' => $logEntry,
                                'field' => $field
                            );
                        }
                    }
                    $newValues[$field] = $value;
                }
                $logEntry->setData($newValues);
            }
            $version = 1;
            $logEntryMeta = $om->getClassMetadata($logEntryClass);
            if ($action !== self::ACTION_CREATE) {
                $version = $this->getNewVersion($logEntryMeta, $om, $object);
            }
            $logEntry->setVersion($version);
            
            $om->persist($logEntry);
            $uow->computeChangeSet($logEntryMeta, $logEntry);
        }
    }
}