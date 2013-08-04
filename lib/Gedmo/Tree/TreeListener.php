<?php

namespace Gedmo\Tree;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\EventArgs;
use Gedmo\Mapping\MappedEventSubscriber;
use Gedmo\Mapping\ObjectManagerHelper as OMH;
use Gedmo\Exception\UnexpectedValueException;
use Gedmo\Exception\InvalidArgumentException;

/**
 * The tree listener handles the synchronization of
 * tree nodes. Can implement different
 * strategies on handling the tree.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TreeListener extends MappedEventSubscriber
{
    /**
     * {@inheritDoc}
     */
    protected $ignoredFilters = array(
        'Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter'
    );

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
     * List of used classes on flush
     *
     * @var array
     */
    private $usedClassesOnFlush = array();

    /**
     * Specifies the list of events to listen
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            'prePersist',
            'preRemove',
            'preUpdate',
            'onFlush',
            'loadClassMetadata',
            'postPersist',
            'postUpdate',
            'postRemove'
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
            $exm = $this->getConfiguration($om, $class);
            if (!$exm) {
                throw new UnexpectedValueException("Tree object class: {$class} must have tree metadata at this point");
            }
            $managerName = 'UnsupportedManager';
            if ($om instanceof \Doctrine\ORM\EntityManager) {
                $managerName = 'ORM';
            } elseif ($om instanceof \Doctrine\ODM\MongoDB\DocumentManager) {
                $managerName = 'ODM\\MongoDB';
            }
            if (!isset($this->strategyInstances[$exm->getStrategy()])) {
                $strategyClass = $this->getNamespace().'\\Strategy\\'.$managerName.'\\'.ucfirst($exm->getStrategy());
                $this->strategyInstances[$exm->getStrategy()] = new $strategyClass($this);
            }
            $this->strategies[$class] = $exm->getStrategy();
        }
        return $this->strategyInstances[$this->strategies[$class]];
    }

    /**
     * Looks for Tree objects being updated
     * for further processing
     *
     * @param EventArgs $event
     */
    public function onFlush(EventArgs $event)
    {
        $om = OMH::getObjectManagerFromEvent($event);
        $uow = $om->getUnitOfWork();

        $this->disableFilters($om);
        // check all scheduled updates for TreeNodes
        foreach (OMH::getScheduledObjectInsertions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($exm = $this->getConfiguration($om, $meta->name)) {
                $this->usedClassesOnFlush[$meta->name] = null;
                $this->getStrategy($om, $meta->name)->processScheduledInsertion($om, $object);
                OMH::recomputeSingleObjectChangeSet($uow, $meta, $object);
            }
        }

        foreach (OMH::getScheduledObjectUpdates($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($exm = $this->getConfiguration($om, $meta->name)) {
                $this->usedClassesOnFlush[$meta->name] = null;
                $this->getStrategy($om, $meta->name)->processScheduledUpdate($om, $object);
            }
        }

        foreach (OMH::getScheduledObjectDeletions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($exm = $this->getConfiguration($om, $meta->name)) {
                $this->usedClassesOnFlush[$meta->name] = null;
                $this->getStrategy($om, $meta->name)->processScheduledDelete($om, $object);
            }
        }

        foreach ($this->getStrategiesUsedForObjects($this->usedClassesOnFlush) as $strategy) {
            $strategy->onFlushEnd($om);
        }
        $this->enableFilters($om);
    }

    /**
     * Updates tree on Node removal
     *
     * @param EventArgs $event
     */
    public function preRemove(EventArgs $event)
    {
        $om = OMH::getObjectManagerFromEvent($event);
        $object = OMH::getObjectFromEvent($event);
        $meta = $om->getClassMetadata(get_class($object));

        if ($this->getConfiguration($om, $meta->name)) {
            $this->disableFilters($om);
            $this->getStrategy($om, $meta->name)->processPreRemove($om, $object);
            $this->enableFilters($om);
        }
    }

    /**
     * Checks for persisted Nodes
     *
     * @param EventArgs $event
     */
    public function prePersist(EventArgs $event)
    {
        $om = OMH::getObjectManagerFromEvent($event);
        $object = OMH::getObjectFromEvent($event);
        $meta = $om->getClassMetadata(get_class($object));

        if ($this->getConfiguration($om, $meta->name)) {
            $this->disableFilters($om);
            $this->getStrategy($om, $meta->name)->processPrePersist($om, $object);
            $this->enableFilters($om);
        }
    }

    /**
     * Checks for updated Nodes
     *
     * @param EventArgs $event
     */
    public function preUpdate(EventArgs $event)
    {
        $om = OMH::getObjectManagerFromEvent($event);
        $object = OMH::getObjectFromEvent($event);
        $meta = $om->getClassMetadata(get_class($object));

        if ($this->getConfiguration($om, $meta->name)) {
            $this->disableFilters($om);
            $this->getStrategy($om, $meta->name)->processPreUpdate($om, $object);
            $this->enableFilters($om);
        }
    }

    /**
     * Checks for pending Nodes to fully synchronize
     * the tree
     *
     * @param EventArgs $event
     */
    public function postPersist(EventArgs $event)
    {
        $om = OMH::getObjectManagerFromEvent($event);
        $object = OMH::getObjectFromEvent($event);
        $meta = $om->getClassMetadata(get_class($object));

        if ($this->getConfiguration($om, $meta->name)) {
            $this->disableFilters($om);
            $this->getStrategy($om, $meta->name)->processPostPersist($om, $object);
            $this->enableFilters($om);
        }
    }

    /**
     * Checks for pending Nodes to fully synchronize
     * the tree
     *
     * @param EventArgs $event
     */
    public function postUpdate(EventArgs $event)
    {
        $om = OMH::getObjectManagerFromEvent($event);
        $object = OMH::getObjectFromEvent($event);
        $meta = $om->getClassMetadata(get_class($object));

        if ($this->getConfiguration($om, $meta->name)) {
            $this->disableFilters($om);
            $this->getStrategy($om, $meta->name)->processPostUpdate($om, $object);
            $this->enableFilters($om);
        }
    }

    /**
     * Checks for pending Nodes to fully synchronize
     * the tree
     *
     * @param EventArgs $event
     */
    public function postRemove(EventArgs $event)
    {
        $om = OMH::getObjectManagerFromEvent($event);
        $object = OMH::getObjectFromEvent($event);
        $meta = $om->getClassMetadata(get_class($object));

        if ($this->getConfiguration($om, $meta->name)) {
            $this->disableFilters($om);
            $this->getStrategy($om, $meta->name)->processPostRemove($om, $object);
            $this->enableFilters($om);
        }
    }

    /**
     * Mapps additional metadata
     *
     * @param EventArgs $event
     */
    public function loadClassMetadata(EventArgs $event)
    {
        $om = OMH::getObjectManagerFromEvent($event);
        $meta = $event->getClassMetadata();
        $this->loadMetadataForObjectClass($om, $meta);
        if (isset($this->extensionMetadatas[$meta->name]) && ($exm = $this->extensionMetadatas[$meta->name])) {
            if (!$exm->isEmpty()) {
                $this->getStrategy($om, $meta->name)->processMetadataLoad($om, $meta);
            }
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
    protected function getStrategiesUsedForObjects(array $classes)
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
