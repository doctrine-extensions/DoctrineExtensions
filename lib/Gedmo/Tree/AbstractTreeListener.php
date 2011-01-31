<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventArgs,
    Gedmo\Mapping\MappedEventSubscriber;

abstract class AbstractTreeListener extends MappedEventSubscriber
{
    /**
     * NestedSet Tree type
     */
    const TYPE_NESTED = 'nested';
    
    /**
     * Closure Tree type
     */
    //const TYPE_CLOSURE = 'closure'; not yet
    
    /**
     * Tree processing strategy
     * 
     * @var StrategyInterface
     */
    protected $strategy = null;
    
    /**
     * Initialize tree listener
     * 
     * @param string $type
     */
    public function __construct($type = 'nested')
    {
        $this->strategy = $this->loadStrategy($type);
    }
    
    /**
     * Get the used strategy for tree processing
     * 
     * @return StrategyInterface
     */
    public function getStrategy()
    {
        return $this->strategy;
    }
    
    /**
     * Looks for Tree objects being updated
     * for further processing
     * 
     * @param EventArgs $args
     * @return void
     */
    public function onFlush(EventArgs $args)
    {
        $om = $this->getObjectManager($args);
        $uow = $om->getUnitOfWork();
        // check all scheduled updates for TreeNodes
        foreach ($this->getScheduledObjectUpdates($uow) as $object) {
            $objectClass = get_class($object);
            if ($config = $this->getConfiguration($om, $objectClass)) {
                $this->strategy->processScheduledUpdate($om, $object);
            }
        }
        $this->strategy->onFlushEnd($om);
    }
    
    /**
     * Updates tree on Node removal
     * 
     * @param EventArgs $args
     * @return void
     */
    public function preRemove(EventArgs $args)
    {
        $om = $this->getObjectManager($args);
        $object = $this->getObject($args);
        $objectClass = get_class($object);
        
        if ($config = $this->getConfiguration($om, $objectClass)) {
            $this->strategy->processScheduledDelete($om, $object);
        }
    }
    
    /**
     * Checks for persisted Nodes
     * 
     * @param EventArgs $args
     * @return void
     */
    public function prePersist(EventArgs $args)
    {
        $om = $this->getObjectManager($args);
        $object = $this->getObject($args);
        $objectClass = get_class($object);
        
        if ($config = $this->getConfiguration($om, $objectClass)) {
            $this->strategy->processPrePersist($om, $object);
        }
    }
    
    /**
     * Checks for pending Nodes to fully synchronize
     * the tree
     * 
     * @param EventArgs $args
     * @return void
     */
    public function postPersist(EventArgs $args)
    {
        $om = $this->getObjectManager($args);
        $uow = $om->getUnitOfWork();
        $object = $this->getObject($args);
        
        if (!$uow->hasPendingInsertions()) {
            $this->strategy->processPostPersist($om, $object);
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
     * {@inheritDoc}
     */
    protected function _getNamespace()
    {
        return __NAMESPACE__;
    }
    
    /**
     * Get the ObjectManager from EventArgs
     *
     * @param EventArgs $args
     * @return object
     */
    abstract protected function getObjectManager(EventArgs $args);
    
    /**
     * Get the Object from EventArgs
     *
     * @param EventArgs $args
     * @return object
     */
    abstract protected function getObject(EventArgs $args);
    
    /**
     * Get the scheduled object updates from a UnitOfWork
     *
     * @param UnitOfWork $uow
     * @return array
     */
    abstract protected function getScheduledObjectUpdates($uow);
    
    /**
     * Loads an adapter for tree processing
     * 
     * @param string $type
     * @return StrategyInterface
     */
    abstract protected function loadStrategy($type);
}