<?php

namespace Gedmo\Sluggable\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Events,
    Doctrine\Common\EventArgs;

/**
 * The SluggableListener handles the generation of slugs
 * for documents.
 * 
 * This behavior can inpact the performance of your application
 * since it does some additional calculations on persisted documents.
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @subpackage SluggableListener
 * @package Gedmo.Sluggable.ODM.MongoDB
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SluggableListener extends AbstractSluggableListener
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
    public function getObjectManager(EventArgs $args)
    {
        return $args->getDocumentManager();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getObject(EventArgs $args)
    {
        return $args->getDocument();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getObjectChangeSet($uow, $object)
    {
        return $uow->getDocumentChangeSet($object);
    }
    
    /**
     * {@inheritdoc}
     */
    public function recomputeSingleObjectChangeSet($uow, $meta, $object)
    {
        $uow->recomputeSingleDocumentChangeSet($meta, $object);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getScheduledObjectUpdates($uow)
    {
        return $uow->getScheduledDocumentUpdates();
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getUniqueSlugResult($om, $object, $meta, array $config, $preferedSlug)
    {
        return array();
    }
}