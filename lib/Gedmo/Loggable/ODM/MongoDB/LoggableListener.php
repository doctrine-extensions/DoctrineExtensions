<?php

namespace Gedmo\Loggable\ODM\MongoDB;

use Gedmo\Loggable\AbstractLoggableListener,
    Doctrine\ODM\MongoDB\Events,
    Doctrine\Common\Persistence\ObjectManager,
    Doctrine\Common\Persistence\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\Cursor,
    Doctrine\ODM\MongoDB\Proxy\Proxy,
    Doctrine\Common\EventArgs;

/**
 * @author Boussekeyt Jules <jules.boussekeyt@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Loggable.ODM.MongoDB
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
    protected $defaultLogEntryDocument = 'Gedmo\Loggable\Document\LogEntry';

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
            $this->defaultLogEntryDocument;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getObjectManager(EventArgs $args)
    {
        return $args->getDocumentManager();
    }

    /**
     * {@inheritdoc}
     */
    protected function getScheduledObjectUpdates($uow)
    {
        return $uow->getScheduledDocumentUpdates();
    }

    /**
     * {@inheritdoc}
     */
    protected function getScheduledObjectInsertions($uow)
    {
        return $uow->getScheduledDocumentInsertions();
    }

    /**
     * {@inheritdoc}
     */
    protected function getScheduledObjectDeletions($uow)
    {
        return $uow->getScheduledDocumentDeletions();
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getObjectChangeSet($uow, $object)
    {
        return $uow->getDocumentChangeSet($object);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getSingleIdentifierFieldName(ClassMetadata $meta)
    {
        return $meta->identifier;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getObject(EventArgs $args)
    {
        return $args->getDocument();
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getNewVersion(ClassMetadata $meta, ObjectManager $om, $object)
    {
        $objectMeta = $om->getClassMetadata(get_class($object));
        $identifierField = $this->getSingleIdentifierFieldName($objectMeta);
        $objectId = $objectMeta->getReflectionProperty($identifierField)->getValue($object);
        
        $qb = $om->createQueryBuilder($meta->name);
        $qb->select('version');
        $qb->field('objectId')->equals($objectId);
        $qb->field('objectClass')->equals($objectMeta->name);
        $qb->sort('version', 'DESC');
        $qb->limit(1);
        $q = $qb->getQuery();
        $q->setHydrate(false);
        
        $result = $q->getSingleResult();
        if ($result) {
            $result = $result['version'] + 1;
        }
        return $result;
    }
}