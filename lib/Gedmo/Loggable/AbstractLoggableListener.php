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
            if ($this->isAllowed('create', $object, $om)) {
                $this->createLog('create', $object, $om);
            }
        }
        foreach ($this->getScheduledObjectUpdates($uow) as $object) {
            if ($this->isAllowed('update', $object, $om)) {
                $this->createLog('update', $object, $om);
            }
        }
        foreach ($this->getScheduledObjectDeletions($uow) as $object) {
            if ($this->isAllowed('delete', $object, $om)) {
                $this->createLog('delete', $object, $om);
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
    private function isAllowed($action, $object, $om)
    {
        $config = $this->getConfiguration($om, get_class($object));

        if (!isset($config['actions'])) {
            return false;
        }

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
    private function createLog($action, $object, $om)
    {
        $class = $this->getObjectClass();
        $log = new $class();
        $user = Configuration::getUser();

        $log->setAction($action);
        $log->setUser($user);
        $log->setObject($object);

        $this->insertLogRecord($om, $log);

        return $log;
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
    abstract protected function getObjectClass();

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
}