<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tree;

use Doctrine\Common\EventArgs;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Exception\UnexpectedValueException;
use Gedmo\Mapping\MappedEventSubscriber;
use Gedmo\Tree\Mapping\Event\TreeAdapter;

/**
 * The tree listener handles the synchronization of
 * tree nodes. Can implement different
 * strategies on handling the tree.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @phpstan-type TreeConfiguration = array{
 *   activate_locking?: bool,
 *   closure?: class-string,
 *   left?: string,
 *   level?: string,
 *   lock_time?: string,
 *   locking_timeout?: int,
 *   parent?: string,
 *   path?: string,
 *   path_source?: string,
 *   path_separator?: string,
 *   path_append_id?: ?bool,
 *   path_starts_with_separator?: bool,
 *   path_ends_with_separator?: bool,
 *   path_hash?: string,
 *   right?: string,
 *   root?: string,
 *   rootIdentifierMethod?: string,
 *   strategy?: string,
 *   useObjectClass?: class-string,
 *   level_base?: int,
 * }
 *
 * @phpstan-method TreeConfiguration getConfiguration(ObjectManager $objectManager, $class)
 *
 * @method TreeAdapter getEventAdapter(EventArgs $args)
 */
class TreeListener extends MappedEventSubscriber
{
    /**
     * Tree processing strategies for object classes
     *
     * @var array<string, string>
     *
     * @phpstan-var array<class-string, string>
     */
    private array $strategies = [];

    /**
     * List of strategy instances
     *
     * @var array<string, Strategy>
     *
     * @phpstan-var array<value-of<self::strategies>, Strategy>
     */
    private array $strategyInstances = [];

    /**
     * List of used classes on flush
     *
     * @var array<string, null>
     *
     * @phpstan-var array<class-string, null>
     */
    private array $usedClassesOnFlush = [];

    /**
     * Specifies the list of events to listen
     *
     * @return string[]
     */
    public function getSubscribedEvents()
    {
        return [
            'prePersist',
            'preRemove',
            'preUpdate',
            'onFlush',
            'loadClassMetadata',
            'postPersist',
            'postUpdate',
            'postRemove',
        ];
    }

    /**
     * Get the used strategy for tree processing
     *
     * @param string $class
     *
     * @return Strategy
     */
    public function getStrategy(ObjectManager $om, $class)
    {
        if (!isset($this->strategies[$class])) {
            $config = $this->getConfiguration($om, $class);
            if ([] === $config) {
                throw new UnexpectedValueException("Tree object class: {$class} must have tree metadata at this point");
            }
            $managerName = 'UnsupportedManager';
            if ($om instanceof EntityManagerInterface) {
                $managerName = 'ORM';
            } elseif ($om instanceof DocumentManager) {
                $managerName = 'ODM\\MongoDB';
            }
            if (!isset($this->strategyInstances[$config['strategy']])) {
                $strategyClass = $this->getNamespace().'\\Strategy\\'.$managerName.'\\'.ucfirst($config['strategy']);

                if (!class_exists($strategyClass)) {
                    throw new InvalidArgumentException($managerName." TreeListener does not support tree type: {$config['strategy']}");
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
     * @return void
     */
    public function onFlush(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $uow = $om->getUnitOfWork();

        // check all scheduled updates for TreeNodes
        foreach ($ea->getScheduledObjectInsertions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($this->getConfiguration($om, $meta->getName())) {
                $this->usedClassesOnFlush[$meta->getName()] = null;
                $this->getStrategy($om, $meta->getName())->processScheduledInsertion($om, $object, $ea);
                $ea->recomputeSingleObjectChangeSet($uow, $meta, $object);
            }
        }

        foreach ($ea->getScheduledObjectUpdates($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($this->getConfiguration($om, $meta->getName())) {
                $this->usedClassesOnFlush[$meta->getName()] = null;
                $this->getStrategy($om, $meta->getName())->processScheduledUpdate($om, $object, $ea);
            }
        }

        foreach ($ea->getScheduledObjectDeletions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($this->getConfiguration($om, $meta->getName())) {
                $this->usedClassesOnFlush[$meta->getName()] = null;
                $this->getStrategy($om, $meta->getName())->processScheduledDelete($om, $object);
            }
        }

        foreach ($this->getStrategiesUsedForObjects($this->usedClassesOnFlush) as $strategy) {
            $strategy->onFlushEnd($om, $ea);
        }
    }

    /**
     * Updates tree on Node removal
     *
     * @return void
     */
    public function preRemove(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));

        if ($this->getConfiguration($om, $meta->getName())) {
            $this->getStrategy($om, $meta->getName())->processPreRemove($om, $object);
        }
    }

    /**
     * Checks for persisted Nodes
     *
     * @return void
     */
    public function prePersist(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));

        if ($this->getConfiguration($om, $meta->getName())) {
            $this->getStrategy($om, $meta->getName())->processPrePersist($om, $object);
        }
    }

    /**
     * Checks for updated Nodes
     *
     * @return void
     */
    public function preUpdate(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));

        if ($this->getConfiguration($om, $meta->getName())) {
            $this->getStrategy($om, $meta->getName())->processPreUpdate($om, $object);
        }
    }

    /**
     * Checks for pending Nodes to fully synchronize
     * the tree
     *
     * @return void
     */
    public function postPersist(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));

        if ($this->getConfiguration($om, $meta->getName())) {
            $this->getStrategy($om, $meta->getName())->processPostPersist($om, $object, $ea);
        }
    }

    /**
     * Checks for pending Nodes to fully synchronize
     * the tree
     *
     * @return void
     */
    public function postUpdate(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));

        if ($this->getConfiguration($om, $meta->getName())) {
            $this->getStrategy($om, $meta->getName())->processPostUpdate($om, $object, $ea);
        }
    }

    /**
     * Checks for pending Nodes to fully synchronize
     * the tree
     *
     * @return void
     */
    public function postRemove(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));

        if ($this->getConfiguration($om, $meta->getName())) {
            $this->getStrategy($om, $meta->getName())->processPostRemove($om, $object, $ea);
        }
    }

    /**
     * Mapps additional metadata
     *
     * @param LoadClassMetadataEventArgs $eventArgs
     *
     * @phpstan-param LoadClassMetadataEventArgs<ClassMetadata<object>, ObjectManager> $eventArgs
     *
     * @return void
     */
    public function loadClassMetadata(EventArgs $eventArgs)
    {
        $om = $eventArgs->getObjectManager();
        $meta = $eventArgs->getClassMetadata();
        $this->loadMetadataForObjectClass($om, $meta);
        if (isset(self::$configurations[$this->name][$meta->getName()]) && self::$configurations[$this->name][$meta->getName()]) {
            $this->getStrategy($om, $meta->getName())->processMetadataLoad($om, $meta);
        }
    }

    protected function getNamespace()
    {
        return __NAMESPACE__;
    }

    /**
     * Get the list of strategy instances used for
     * given object classes
     *
     * @phpstan-param array<class-string, null> $classes
     *
     * @return array<string, Strategy>
     *
     * @phpstan-return array<value-of<self::strategies>, Strategy>
     */
    protected function getStrategiesUsedForObjects(array $classes)
    {
        $strategies = [];
        foreach ($classes as $name => $opt) {
            if (isset($this->strategies[$name]) && !isset($strategies[$this->strategies[$name]])) {
                $strategies[$this->strategies[$name]] = $this->strategyInstances[$this->strategies[$name]];
            }
        }

        return $strategies;
    }
}
