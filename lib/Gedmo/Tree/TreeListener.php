<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventArgs,
    Gedmo\Mapping\MappedEventSubscriber,
    Doctrine\Common\Persistence\ObjectManager;

/**
 * The tree listener handles the synchronization of
 * tree nodes. Can implement diferent
 * strategies on handling the tree.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree
 * @subpackage TreeListener
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TreeListener extends MappedEventSubscriber
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
     * Specifies the list of events to listen
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            'prePersist',
            'postPersist',
            'preRemove',
            'onFlush',
            'loadClassMetadata'
        );
    }

    /**
     * Get the used strategy for tree processing
     *
     * @param ObjectManager $om
     * @param string $class
     * @return Strategy
     */
    public function getStrategy(ObjectManager $om, $class)
    {
        if (!isset($this->strategies[$class])) {
            $config = $this->getConfiguration($om, $class);
            if (!$config) {
                throw new \Gedmo\Exception\UnexpectedValueException("Tree object class: {$class} must have tree metadata at this point");
            }
            $managerName = 'UnsupportedManager';
            if ($om instanceof \Doctrine\ORM\EntityManager) {
                $managerName = 'ORM';
            } elseif ($om instanceof \Doctrine\ODM\MongoDB\DocumentManager) {
                $managerName = 'ODM';
            }
            if (!isset($this->strategyInstances[$config['strategy']])) {
                $strategyClass = $this->getNamespace().'\\Strategy\\'.$managerName.'\\'.ucfirst($config['strategy']);
                if (!class_exists($strategyClass)) {
                    throw new \Gedmo\Exception\InvalidArgumentException($managerName." TreeListener does not support tree type: {$config['strategy']}");
                }
                $this->strategyInstances[$config['strategy']] = new $strategyClass($this);
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
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $uow = $om->getUnitOfWork();
        // check all scheduled updates for TreeNodes
        $usedClasses = array();

        foreach ($ea->getScheduledObjectInsertions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($config = $this->getConfiguration($om, $meta->name)) {
                $usedClasses[$meta->name] = null;
                $this->getStrategy($om, $meta->name)->processScheduledInsertion($om, $object);
                $uow->recomputeSingleEntityChangeSet($meta, $object);
            }
        }

        foreach ($ea->getScheduledObjectUpdates($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($config = $this->getConfiguration($om, $meta->name)) {
                $usedClasses[$meta->name] = null;
                $this->getStrategy($om, $meta->name)->processScheduledUpdate($om, $object);
            }
        }

        foreach ($ea->getScheduledObjectDeletions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($config = $this->getConfiguration($om, $meta->name)) {
                $usedClasses[$meta->name] = null;
                $this->getStrategy($om, $meta->name)->processScheduledDelete($om, $object);
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
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));

        if ($config = $this->getConfiguration($om, $meta->name)) {
            $this->getStrategy($om, $meta->name)->processPreRemove($om, $object);
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
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
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
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
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
        $ea = $this->getEventAdapter($eventArgs);
        $om = $ea->getObjectManager();
        $meta = $eventArgs->getClassMetadata();
        $this->loadMetadataForObjectClass($om, $meta);
        if (isset($this->configurations[$meta->name]) && $this->configurations[$meta->name]) {
            $config = $this->configurations[$meta->name];
            $this->getStrategy($om, $meta->name)->processMetadataLoad($om, $meta);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function getNamespace()
    {
        return __NAMESPACE__;
    }

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
