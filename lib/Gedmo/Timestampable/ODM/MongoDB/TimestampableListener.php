<?php

namespace Gedmo\Timestampable\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Events,
    Doctrine\Common\EventArgs,
    Gedmo\Timestampable\AbstractTimestampableListener;

/**
 * The Timestampable listener handles the update of
 * dates on creation and update of entity.
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Timestampable.ODM.MongoDB
 * @subpackage TimestampableListener
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TimestampableListener extends AbstractTimestampableListener
{    
    /**
     * Specifies the list of events to listen
     * 
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            Events::prePersist,
            Events::onFlush,
            Events::loadClassMetadata
        );
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
    protected function getObject(EventArgs $args)
    {
        return $args->getDocument();
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
    protected function getScheduledObjectUpdates($uow)
    {
        return $uow->getScheduledDocumentUpdates();
    }
    
	/**
     * {@inheritdoc}
     */
    protected function recomputeSingleObjectChangeSet($uow, $meta, $object)
    {
        $uow->recomputeSingleDocumentChangeSet($meta, $object);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getDateValue($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);
        if (isset($mapping['type']) && $mapping['type'] === 'timestamp') {
            return time();
        }
        return new \DateTime();
    }
}
