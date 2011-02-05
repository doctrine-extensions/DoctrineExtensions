<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventArgs,
    Gedmo\Mapping\MappedEventSubscriber;

abstract class AbstractTreeListener extends MappedEventSubscriber
{    
    /**
     * Tree processing strategies for object classes
     * 
     * @var array
     */
    private $strategies = array();
    
    /**
     * List of strategy instances
     * 
     * @var array
     */
    private $strategyInstances = array();
    
    /**
     * List of object classes in post persist 
     * 
     * @var array
     */
    private $postPersistClasses = array();
    
    /**
     * Get the used strategy for tree processing
     * 
     * @param object $om - object manager
     * @param string $class
     * @return StrategyInterface
     */
    public function getStrategy($om, $class)
    {
        if (!isset($this->strategies[$class])) {
            $config = $this->getConfiguration($om, $class);
            if (!$config) {
                throw new \Gedmo\Exception\UnexpectedValueException("Tree object class: {$class} must have tree metadata at this point");
            }
            // current listener can be only ODM or ORM
            if (!isset($this->strategyInstances[$config['strategy']])) {
                $this->strategyInstances[$config['strategy']] = $this->loadStrategy($config['strategy']);
            }
            $this->strategies[$class] = $config['strategy'];
        }
        return $this->strategyInstances[$this->strategies[$class]];
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
        $usedClasses = array();
        foreach ($this->getScheduledObjectUpdates($uow) as $object) {
            $objectClass = get_class($object);
            if ($config = $this->getConfiguration($om, $objectClass)) {
                $usedClasses[$objectClass] = null;
                $this->getStrategy($om, $objectClass)->processScheduledUpdate($om, $object);
            }
        }
        foreach ($this->getStrategiesUsedForObjects($usedClasses) as $strategy) {
            $strategy->onFlushEnd($om);
        }
        $this->postPersistClasses = array();
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
            $this->getStrategy($om, $objectClass)->processScheduledDelete($om, $object);
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
            $this->getStrategy($om, $objectClass)->processPrePersist($om, $object);
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
        $objectClass = get_class($object);
        
        if ($config = $this->getConfiguration($om, $objectClass)) {
            $this->getStrategy($om, $objectClass)->processPostPersist($om, $object);
            $this->postPersistClasses[$objectClass] = null;
        }
        
        if (!$uow->hasPendingInsertions()) {
            foreach ($this->getStrategiesUsedForObjects($this->postPersistClasses) as $strategy) {
                $strategy->processPendingInserts($om);
            }
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
    protected function getNamespace()
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
    
    /**
     * Get the list of strategy instances used for
     * given object classes
     * 
     * @param array $classes
     * @return array
     */
    private function getStrategiesUsedForObjects(array $classes)
    {
        $strategies = array();
        foreach ($classes as $name => $opt) {
            if (isset($this->strategies[$name]) && !isset($strategies[$this->strategies[$name]])) {
                $strategies[$this->strategies[$name]] = $this->strategyInstances[$this->strategies[$name]];
            }
        }
        return $strategies;
    }
}