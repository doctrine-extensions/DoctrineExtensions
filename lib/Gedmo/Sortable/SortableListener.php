<?php

namespace Gedmo\Sortable;

use Doctrine\Common\EventArgs;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\UnitOfWork;
use Gedmo\Mapping\MappedEventSubscriber;
use Doctrine\ORM\Proxy\Proxy;

/**
 * The SortableListener maintains a sort index on your entities
 * to enable arbitrary sorting.
 *
 * This behavior can impact the performance of your application
 * since it does some additional calculations on persisted objects.
 *
 * @author Lukas Botsch <lukas.botsch@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SortableListener extends MappedEventSubscriber
{
    private $relocations = array();
    private $maxPositions = array();

    /**
     * Specifies the list of events to listen
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            'onFlush',
            'loadClassMetadata',
            'prePersist',
        );
    }

    /**
     * Maps additional metadata
     *
     * @param EventArgs $args
     */
    public function loadClassMetadata(EventArgs $args)
    {
        $eventAdapter = $this->getEventAdapter($args);
        $this->loadMetadataForObjectClass($eventAdapter->getObjectManager(), $args->getClassMetadata());
    }

    /**
     * Update position on objects being updated during flush
     * if they require changing
     *
     * @param EventArgs $args
     */
    public function onFlush(EventArgs $args)
    {
        $eventAdapter = $this->getEventAdapter($args);
        $om = $eventAdapter->getObjectManager();
        $uow = $om->getUnitOfWork();

        // process all objects being deleted
        foreach ($eventAdapter->getScheduledObjectDeletions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($config = $this->getConfiguration($om, $meta->name)) {
                $this->processDeletion($eventAdapter, $config, $meta, $object);
            }
        }

        // process all objects being updated
        foreach ($eventAdapter->getScheduledObjectUpdates($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($config = $this->getConfiguration($om, $meta->name)) {
                $this->processUpdate($eventAdapter, $config, $meta, $object);
            }
        }

        // process all objects being inserted
        foreach ($eventAdapter->getScheduledObjectInsertions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($config = $this->getConfiguration($om, $meta->name)) {
                $this->processInsert($eventAdapter, $config, $meta, $object);
            }
        }
        $this->processRelocations($eventAdapter);
    }

    /**
     * Update maxPositions as needed
     *
     * @param EventArgs $args
     */
    public function prePersist(EventArgs $args)
    {
        $eventAdapter = $this->getEventAdapter($args);
        $om = $eventAdapter->getObjectManager();
        $object = $eventAdapter->getObject();
        $meta = $om->getClassMetadata(get_class($object));

        if ($config = $this->getConfiguration($om, $meta->name)) {
            // Get groups
            $groups = $this->getGroups($meta, $config, $object);

            // Get hash
            $hash = $this->getHash($meta, $groups, $object, $config);

            // Get max position
            if (!isset($this->maxPositions[$hash])) {
                $this->maxPositions[$hash] = $this->getMaxPosition($eventAdapter, $meta, $config, $object);
            }
        }
    }

    /**
     * Computes node positions and updates the sort field in memory and in the db
     * @param object $eventAdapter \Gedmo\Sortable\Mapping\Event\SortableAdapter
     */
    private function processInsert($eventAdapter, $config, $meta, $object)
    {
        $em = $eventAdapter->getObjectManager();
        $uow = $em->getUnitOfWork();

        $old = $meta->getReflectionProperty($config['position'])->getValue($object);
        $newPosition = $meta->getReflectionProperty($config['position'])->getValue($object);

        if (is_null($newPosition)) {
            $newPosition = -1;
        }

        // Get groups
        $groups = $this->getGroups($meta, $config, $object);

        // Get hash
        $hash = $this->getHash($meta, $groups, $object, $config);

        // Get max position
        if (!isset($this->maxPositions[$hash])) {
            $this->maxPositions[$hash] = $this->getMaxPosition($em, $meta, $config, $object);
        }

        // Compute position if it is negative
        if ($newPosition < 0) {
            $newPosition += $this->maxPositions[$hash] + 2; // position == -1 => append at end of list
            if ($newPosition < 0) $newPosition = 0;
        }

        // Set position to max position if it is too big
        $newPosition = min(array($this->maxPositions[$hash] + 1, $newPosition));

        // Compute relocations
        $relocation = array($hash, $config['useObjectClass'], $groups, $newPosition, -1, +1);

        // Apply existing relocations
        $applyDelta = 0;
        if (isset($this->relocations[$hash])) {
            foreach ($this->relocations[$hash]['deltas'] as $delta) {
                if ($delta['start'] <= $newPosition
                        && ($delta['stop'] > $newPosition || $delta['stop'] < 0)) {
                    $applyDelta += $delta['delta'];
                }
            }
        }
        $newPosition += $applyDelta;

        // Add relocations
        call_user_func_array(array($this, 'addRelocation'), $relocation);

        // Set new position
        if ($old < 0 || is_null($old)) {
            $meta->getReflectionProperty($config['position'])->setValue($object, $newPosition);
            $eventAdapter->recomputeSingleObjectChangeSet($uow, $meta, $object);
        }
    }

    /**
     * Computes node positions and updates the sort field in memory and in the db
     * @param object $eventAdapter \Gedmo\Sortable\Mapping\Event\SortableAdapter
     */
    private function processUpdate($eventAdapter, $config, $meta, $object)
    {
        $em = $eventAdapter->getObjectManager();
        $uow = $em->getUnitOfWork();

        $changed = false;
        $changeSet = $eventAdapter->getObjectChangeSet($uow, $object);

        // Get groups
        $groups = $this->getGroups($meta, $config, $object);

        // handle old groups
        $oldGroups = $groups;
        foreach (array_keys($groups) as $group) {
            if (array_key_exists($group, $changeSet)) {
                $changed = true;
                $oldGroups[$group] = $changeSet[$group][0];
            }
        }

        if ($changed) {
            $oldHash = $this->getHash($meta, $oldGroups, $object, $config);
            $this->maxPositions[$oldHash] = $this->getMaxPosition($eventAdapter, $meta, $config, $object, $oldGroups);
            $this->addRelocation($oldHash, $config['useObjectClass'], $oldGroups, $meta->getReflectionProperty($config['position'])->getValue($object) + 1, $this->maxPositions[$oldHash] + 1, -1, true);
        }

        if (array_key_exists($config['position'], $changeSet)) {
            // position was manually updated
            $oldPosition = $changeSet[$config['position']][0];
            $newPosition = $changeSet[$config['position']][1];
            $changed = $changed || $oldPosition != $newPosition;
        } elseif ($changed) {
            // group has changed, so position has to be recalculated
            $oldPosition = -1;
            $newPosition = -1;
            // specific case
        }
        if (!$changed) return;

        // Get hash
        $hash = $this->getHash($meta, $groups, $object, $config);

        // Get max position
        if (!isset($this->maxPositions[$hash])) {
            $this->maxPositions[$hash] = $this->getMaxPosition($eventAdapter, $meta, $config, $object);
        }

        // Compute position if it is negative
        if ($newPosition < 0) {
            $newPosition += $this->maxPositions[$hash] + 2; // position == -1 => append at end of list

            if ($newPosition < 0) {
                $newPosition = 0;
            }
        } else {
            $newPosition = min(array($this->maxPositions[$hash], $newPosition));
        }

        // Compute relocations
        /*
        CASE 1: shift backwards
        |----0----|----1----|----2----|----3----|----4----|
        |--node1--|--node2--|--node3--|--node4--|--node5--|
        Update node4: setPosition(1)
        --> Update position + 1 where position in [1,3)
        |--node1--|--node4--|--node2--|--node3--|--node5--|
        CASE 2: shift forward
        |----0----|----1----|----2----|----3----|----4----|
        |--node1--|--node2--|--node3--|--node4--|--node5--|
        Update node2: setPosition(3)
        --> Update position - 1 where position in (1,3]
        |--node1--|--node3--|--node4--|--node2--|--node5--|
        */
        $relocation = null;
        if ($oldPosition === -1) {
            // special case when group changes
            $relocation = array($hash, $config['useObjectClass'], $groups, $newPosition, -1, +1);
        } elseif ($newPosition < $oldPosition) {
            $relocation = array($hash, $config['useObjectClass'], $groups, $newPosition, $oldPosition, +1);
        } elseif ($newPosition > $oldPosition) {
            $relocation = array($hash, $config['useObjectClass'], $groups, $oldPosition + 1, $newPosition + 1, -1);
        }

        // Apply existing relocations
        $applyDelta = 0;
        if (isset($this->relocations[$hash])) {
            foreach ($this->relocations[$hash]['deltas'] as $delta) {
                if ($delta['start'] <= $newPosition
                        && ($delta['stop'] > $newPosition || $delta['stop'] < 0)) {
                    $applyDelta += $delta['delta'];
                }
            }
        }
        $newPosition += $applyDelta;

        if ($relocation) {
            // Add relocation
            call_user_func_array(array($this, 'addRelocation'), $relocation);
        }

        // Set new position
        $meta->getReflectionProperty($config['position'])->setValue($object, $newPosition);
        $eventAdapter->recomputeSingleObjectChangeSet($uow, $meta, $object);
    }

    /**
     * Computes node positions and updates the sort field in memory and in the db
     * @param object $eventAdapter \Gedmo\Sortable\Mapping\Event\SortableAdapter
     */
    private function processDeletion($eventAdapter, $config, $meta, $object)
    {
        $position = $meta->getReflectionProperty($config['position'])->getValue($object);

        // Get groups
        $groups = $this->getGroups($meta, $config, $object);

        // Get hash
        $hash = $this->getHash($meta, $groups, $object, $config);

        // Get max position
        if (!isset($this->maxPositions[$hash])) {
            $this->maxPositions[$hash] = $this->getMaxPosition($eventAdapter, $meta, $config, $object);
        }

        // Add relocation
        $this->addRelocation($hash, $config['useObjectClass'], $groups, $position, -1, -1);
    }

    private function processRelocations($eventAdapter)
    {
        $em = $eventAdapter->getObjectManager();
        foreach ($this->relocations as $hash => $relocation) {
            $config = $this->getConfiguration($em, $relocation['name']);
            foreach ($relocation['deltas'] as $delta) {
                if ($delta['start'] > $this->maxPositions[$hash] || $delta['delta'] == 0) {
                    continue;
                }
                $eventAdapter->updatePositions($relocation, $delta, $config);
                $meta = $em->getClassMetadata($relocation['name']);
                if (property_exists($meta, 'rootDocumentName')) {
                    $metaRootObjectName = $meta->rootDocumentName;
                } else {
                    $metaRootObjectName = $meta->rootEntityName;
                }

                // now walk through the unit of work in memory objects and sync those
                $uow = $em->getUnitOfWork();
                foreach ($uow->getIdentityMap() as $className => $objects) {
                    // for inheritance mapped classes, only root is always in the identity map
                    if ($className !== $metaRootObjectName || !$this->getConfiguration($em, $className)) {
                        continue;
                    }
                    foreach ($objects as $object) {
                        if ($object instanceof Proxy && !$object->__isInitialized__) {
                            continue;
                        }

                        // if the entity's position is already changed, stop now
                        if (array_key_exists($config['position'], $eventAdapter->getObjectChangeSet($uow, $object))) {
                            continue;
                        }

                        $oid = spl_object_hash($object);
                        $pos = $meta->getReflectionProperty($config['position'])->getValue($object);
                        $matches = $pos >= $delta['start'];
                        $matches = $matches && ($delta['stop'] <= 0 || $pos < $delta['stop']);
                        $value = reset($relocation['groups']);
                        while ($matches && ($group = key($relocation['groups']))) {
                            $gr = $meta->getReflectionProperty($group)->getValue($object);
                            if (null === $value) {
                                $matches = $gr === null;
                            } else {
                                $matches = $gr === $value;
                            }
                            $value = next($relocation['groups']);
                        }
                        if ($matches) {
                            $meta->getReflectionProperty($config['position'])->setValue($object, $pos + $delta['delta']);
                            $eventAdapter->setOriginalObjectProperty($uow, $oid, $config['position'], $pos + $delta['delta']);
                        }
                    }
                }
            }
        }

        // Clear relocations
        $this->relocations = array();
        $this->maxPositions = array();
    }

    private function getHash($meta, $groups, $object, &$config)
    {
        $data = $config['useObjectClass'];
        foreach ($groups as $group => $val) {
            if($val instanceof \DateTime) {
                $val = $val->format('c');
            } elseif (is_object($val)) {
                $val = spl_object_hash($val);
            }
            $data .= $group.$val;
        }
        return md5($data);
    }

    private function getMaxPosition($eventAdapter, $meta, $config, $object, array $groups = array())
    {
        $em = $eventAdapter->getObjectManager();
        $uow = $em->getUnitOfWork();
        $maxPos = null;

        // Get groups
        if(!sizeof($groups))
        {
            $groups = $this->getGroups($meta, $config, $object);
        }

        // Get hash
        $hash = $this->getHash($meta, $groups, $object, $config);

        // Check for cached max position
        if (isset($this->maxPositions[$hash])) {
            return $this->maxPositions[$hash];
        }

        // Check for groups that are associations. If the value is an object and is
        // scheduled for insert, it has no identifier yet and is obviously new
        // see issue #226
        foreach ($groups as $val) {
            if (is_object($val) && ($uow->isScheduledForInsert($val) || !$em->getMetadataFactory()->isTransient(ClassUtils::getClass($val)) && UnitOfWork::STATE_MANAGED !== $eventAdapter->getObjectState($uow, $val))) {
                return -1;
            }
        }

        $maxPos = $eventAdapter->getMaxPosition($config, $meta, $groups);
        if (is_null($maxPos)) $maxPos = -1;
        
        return intval($maxPos);
    }

    /**
     * Add a relocation rule
     * @param string $hash The hash of the sorting group
     * @param string $class The object class
     * @param array $groups The sorting groups
     * @param int $start Inclusive index to start relocation from
     * @param int $stop Exclusive index to stop relocation at
     * @param int $delta The delta to add to relocated nodes
     */
    private function addRelocation($hash, $class, $groups, $start, $stop, $delta)
    {
        if (!array_key_exists($hash, $this->relocations)) {
            $this->relocations[$hash] = array('name' => $class, 'groups' => $groups, 'deltas' => array());
        }

        try {
            $newDelta = array('start' => $start, 'stop' => $stop, 'delta' => $delta);
            array_walk($this->relocations[$hash]['deltas'], function(&$val, $idx, $needle) {
                if ($val['start'] == $needle['start'] && $val['stop'] == $needle['stop']) {
                    $val['delta'] += $needle['delta'];
                    throw new \Exception("Found delta. No need to add it again.");
                }
            }, $newDelta);
            $this->relocations[$hash]['deltas'][] = $newDelta;
        } catch (\Exception $e) {}
    }

    /**
     * @param $meta
     * @param $config
     * @param $object
     *
     * @return array
     */
    private function getGroups($meta, $config, $object)
    {
        $groups = array();
        if (isset($config['groups'])) {
            foreach ($config['groups'] as $group) {
                $groups[$group] = $meta->getReflectionProperty($group)->getValue($object);
            }
        }

        return $groups;
    }

    /**
     * {@inheritDoc}
     */
    protected function getNamespace()
    {
        return __NAMESPACE__;
    }
}
