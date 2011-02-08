<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventArgs,
    Gedmo\Mapping\MappedEventSubscriber;

/**
 * The base tree listener model.
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree
 * @subpackage AbstractTreeListener
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
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
            $meta = $om->getClassMetadata(get_class($object));
            if ($config = $this->getConfiguration($om, $meta->name)) {
                $usedClasses[$meta->name] = null;
                $this->getStrategy($om, $meta->name)->processScheduledUpdate($om, $object);
            }
        }
        foreach ($this->getStrategiesUsedForObjects($usedClasses) as $strategy) {
            $strategy->onFlushEnd($om);
        }
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
        $meta = $om->getClassMetadata(get_class($object));
        
        if ($config = $this->getConfiguration($om, $meta->name)) {
            $this->getStrategy($om, $meta->name)->processScheduledDelete($om, $object);
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
        $meta = $om->getClassMetadata(get_class($object));
        
        if ($config = $this->getConfiguration($om, $meta->name)) {
            $this->getStrategy($om, $meta->name)->processPrePersist($om, $object);
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
        $object = $this->getObject($args);
        $meta = $om->getClassMetadata(get_class($object));
        
        if ($config = $this->getConfiguration($om, $meta->name)) {
            $this->getStrategy($om, $meta->name)->processPostPersist($om, $object);
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