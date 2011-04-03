<?php

namespace Gedmo\Tree\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Events,
    Doctrine\Common\EventArgs,
    Gedmo\Tree\AbstractTreeListener;

/**
 * The tree listener handles the synchronization of
 * tree nodes for documents. Can implement diferent
 * strategies on handling the tree.
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree.ODM.MongoDB
 * @subpackage TreeListener
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TreeListener extends AbstractTreeListener
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
            Events::postPersist,
            Events::preRemove,
            Events::onFlush,
            Events::loadClassMetadata
        );
    }
    
    /**
     * {@inheritdoc}
     */
    protected function loadStrategy($type)
    {
        $class = $this->getNamespace() . '\Strategy\ODM\\' . ucfirst($type);
        if (!class_exists($class)) {
            throw new \Gedmo\Exception\InvalidArgumentException("ODM TreeListener does not support tree type: {$type}");
        }
        return new $class($this);
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
}