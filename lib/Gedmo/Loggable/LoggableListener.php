<?php

namespace Gedmo\Loggable;

use Gedmo\Loggable\AbstractLoggableListener,
    Doctrine\Common\Persistence\ObjectManager,
    Doctrine\Common\Persistence\Mapping\ClassMetadata,
    Doctrine\ORM\Events,
    Doctrine\Common\EventArgs;

/**
 * @author Boussekeyt Jules <jules.boussekeyt@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Loggable
 * @subpackage LoggableListener
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class LoggableListener extends AbstractLoggableListener
{
    /**
     * The default LogEntry class used to store the logs
     *
     * @var string
     */
    protected $defaultLogEntryEntity = 'Gedmo\Loggable\Entity\LogEntry';

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return array(
            Events::onFlush, 
            Events::loadClassMetadata,
            Events::postPersist
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getLogEntryClass(array $config, $class)
    {
        return isset($this->configurations[$class]['logEntryClass']) ?
            $this->configurations[$class]['logEntryClass'] : 
            $this->defaultLogEntryEntity;
    }

    /**
     * {@inheritdoc}
     */
    protected function isTransient($logEntry)
    {
        return !$logEntry instanceof Entity\AbstractLogEntry;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getObjectManager(EventArgs $args)
    {
        return $args->getEntityManager();
    }

    /**
     * {@inheritdoc}
     */
    protected function getScheduledObjectUpdates($uow)
    {
        return $uow->getScheduledEntityUpdates();
    }

    /**
     * {@inheritdoc}
     */
    protected function getScheduledObjectInsertions($uow)
    {
        return $uow->getScheduledEntityInsertions();
    }

    /**
     * {@inheritdoc}
     */
    protected function getScheduledObjectDeletions($uow)
    {
        return $uow->getScheduledEntityDeletions();
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getObjectChangeSet($uow, $object)
    {
        return $uow->getEntityChangeSet($object);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getSingleIdentifierFieldName(ClassMetadata $meta)
    {
        return $meta->getSingleIdentifierFieldName();
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getObject(EventArgs $args)
    {
        return $args->getEntity();
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getNewVersion(ClassMetadata $meta, ObjectManager $om, $object)
    {
        $objectMeta = $om->getClassMetadata(get_class($object));
        $identifierField = $this->getSingleIdentifierFieldName($objectMeta);
        $objectId = $objectMeta->getReflectionProperty($identifierField)->getValue($object);
        
        $dql = "SELECT MAX(log.version) FROM {$meta->name} log";
        $dql .= " WHERE log.objectId = :objectId";
        $dql .= " AND log.objectClass = :objectClass";
        
        $q = $om->createQuery($dql);
        $q->setParameters(array(
            'objectId' => $objectId,
            'objectClass' => $objectMeta->name
        ));
        return $q->getSingleScalarResult() + 1;
    }
}