<?php

namespace Gedmo\Tree;

use Doctrine\ORM\Events,
    Doctrine\Common\EventArgs;

/**
 * The tree listener handles the synchronization of
 * tree nodes for entities. Can implement diferent
 * strategies on handling the tree.
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree
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
        $class = $this->_getNamespace() . '\Strategy\ORM\\' . ucfirst($type);
        if (!class_exists($class)) {
            throw new \Gedmo\Exception\InvalidArgumentException("ORM TreeListener does not support tree type: {$type}");
        }
        return new $class($this);
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
    protected function getObject(EventArgs $args)
    {
        return $args->getEntity();
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
    protected function getScheduledObjectUpdates($uow)
    {
        return $uow->getScheduledEntityUpdates();
    }
}