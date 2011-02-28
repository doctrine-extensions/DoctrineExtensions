<?php

namespace Gedmo\Loggable;

use Doctrine\Common\EventArgs,
    Gedmo\Mapping\MappedEventSubscriber,
    Doctrine\ODM\MongoDB\Event\OnFlushEventArgs;

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
     * List of logs which do not have the foreign
     * key generated yet - MySQL case. These logs
     * will be updated with new keys on postPersist event
     *
     * @var array
     */
    protected $pendingLogInserts = array();

    protected static $user;

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
     * @param $user string
     */
    public static function setUser($user)
    {
        self::$user = $user;
    }

    /**
     * @return string
     */
    protected function getUser()
    {
        return self::$user;
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
            if ($this->isObjectLoggableForAction($om, $object, 'create')) {
                $this->handleObjectLogging($om, $object, 'create', true);
            }
        }

        foreach ($this->getScheduledObjectUpdates($uow) as $object) {
            if ($this->isObjectLoggableForAction($om, $object, 'update')) {
                $this->handleObjectLogging($om, $object, 'update', false);
            }
        }

        foreach ($this->getScheduledObjectDeletions($uow) as $object) {
            if ($this->isObjectLoggableForAction($om, $object, 'delete')) {
                $this->handleObjectLogging($om, $object, 'delete', false);
            }
        }
    }

    /**
     * Checks for inserted object to update their log
     * foreign keys
     *
     * @param EventArgs $args
     * @return void
     */
    public function postPersist(EventArgs $args)
    {
        $om = $this->getObjectManager($args);
        $object = $this->getObject($args);
        $meta = $om->getClassMetadata(get_class($object));
        // check if entity is tracked by loggable and without foreign key
        if (array_key_exists($meta->name, $this->configurations) && count($this->pendingLogInserts)) {
            $oid = spl_object_hash($object);

            // there should be single identifier
            $identifierField = $this->getSingleIdentifierFieldName($meta);
            $logMeta = $om->getClassMetadata($this->getLogClass($meta->name));
            if (array_key_exists($oid, $this->pendingLogInserts)) {
                // load the pending logs without key
                $log = $this->pendingLogInserts[$oid];
                $logMeta->getReflectionProperty('foreignKey')->setValue(
                    $log,
                    $meta->getReflectionProperty($identifierField)->getValue($object)
                );
                $this->insertLogRecord($om, $log);
            }
        }
    }

    /**
     * is the object allowed to be logged for this action ?
     *
     * @param  $action string
     * @param  $om
     * @param  $object
     * @return bool
     */
    protected function isObjectLoggableForAction($om, $object, $action)
    {
        $config = $this->getConfiguration($om, get_class($object));

        // if object hasn't mapping informations
        if (!isset($config['actions'])) {
            return false;
        }

        // if object action isn't allowed
        if (!in_array($action, $config['actions'])) {
            return false;
        }

        return true;
    }

    /**
     * Create a new Log instance
     *
     * @param  $om
     * @param  $action string
     * @param  $object
     * @return Log
     */
    protected function handleObjectLogging($om, $object, $action, $isInsert)
    {
        $meta = $om->getClassMetadata(get_class($object));
        // no need cache, metadata is loaded only once in MetadataFactoryClass
        $logClass = $this->getLogClass($meta->name);
        $logMetadata = $om->getClassMetadata($logClass);

        // check for the availability of the primary key
        $identifierField = $this->getSingleIdentifierFieldName($meta);
        $objectId = $meta->getReflectionProperty($identifierField)->getValue($object);
        if (!$object && $isInsert) {
            $objectId = null;
        }

        $user = $this->getUser();

        // create new log
        $log = new $logClass();
        $logMetadata->getReflectionProperty('action')->setValue($log, $action);
        $logMetadata->getReflectionProperty('user')->setValue($log, $user);
        $logMetadata->getReflectionProperty('objectClass')->setValue($log, $meta->name);
        $logMetadata->getReflectionProperty('foreignKey')->setValue($log, $objectId);

        // set the logged field, take value using reflection
        //$logMetadata->getReflectionProperty('content')->setValue($log, $meta->getReflectionProperty($field)->getValue($object));

        if ($isInsert && null === $objectId) {
            // if we do not have the primary key yet available
            // keep this log in memory to insert it later with foreign key
            $this->pendingLogInserts[spl_object_hash($object)] = $log;
        } else {
            $this->insertLogRecord($om, $log);
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
     * Inserts the log record
     *
     * @param object $om - object manager
     * @param object $log
     * @return void
     */
    abstract protected function insertLogRecord($om, $log);

    
    /**
     * Get the class of a new Log
     *
     * @param EventArgs $args
     * @return object
     */
    abstract protected function getLogClass();

    /**
     * Get the Object from EventArgs
     *
     * @param EventArgs $args
     * @return object
     */
    abstract protected function getObject(EventArgs $args);
        
    /**
     * Get the ObjectManager from EventArgs
     *
     * @param EventArgs $args
     * @return object
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
     * Get the single identifier field name
     *
     * @param ClassMetadata $meta
     * @throws MappingException - if identifier is composite
     * @return string
     */
    abstract protected function getSingleIdentifierFieldName($meta);

}