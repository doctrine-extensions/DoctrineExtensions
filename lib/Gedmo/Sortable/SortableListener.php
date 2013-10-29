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
        $ea = $this->getEventAdapter($args);
        $this->loadMetadataForObjectClass($ea->getObjectManager(), $args->getClassMetadata());
    }

    /**
     * Update position on objects being updated during flush
     * if they require changing
     *
     * @param EventArgs $args
     */
    public function onFlush(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $uow = $om->getUnitOfWork();

        // process all objects being deleted
        foreach ($ea->getScheduledObjectDeletions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($config = $this->getConfiguration($om, $meta->name)) {
                $this->processDeletion($om, $config, $meta, $object);
            }
        }

        // process all objects being updated
        foreach ($ea->getScheduledObjectUpdates($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($config = $this->getConfiguration($om, $meta->name)) {
                $this->processUpdate($om, $config, $meta, $object);
            }
        }

        // process all objects being inserted
        foreach ($ea->getScheduledObjectInsertions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($config = $this->getConfiguration($om, $meta->name)) {
                $this->processInsert($om, $config, $meta, $object);
            }
        }
        $this->processRelocations($om);
    }

    /**
     * Update maxPositions as needed
     *
     * @param EventArgs $args
     */
    public function prePersist(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));

        if ($config = $this->getConfiguration($om, $meta->name)) {
            // Get groups
            $groups = $this->getGroups($meta, $config, $object);

            // Get hash
            $hash = $this->getHash($meta, $groups, $object, $config);

            // Get max position
            if (!isset($this->maxPositions[$hash])) {
                $this->maxPositions[$hash] = $this->getMaxPosition($om, $meta, $config, $object);
            }
        }
    }

    /**
     * Computes node positions and updates the sort field in memory and in the db
     * @param object $em ObjectManager
     */
    private function processInsert($em, $config, $meta, $object)
    {
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
            $uow->recomputeSingleEntityChangeSet($meta, $object);
        }
    }

    /**
     * Computes node positions and updates the sort field in memory and in the db
     * @param object $em ObjectManager
     */
    private function processUpdate($em, $config, $meta, $object)
    {
        $uow = $em->getUnitOfWork();

        $changed = false;
        $changeSet = $uow->getEntityChangeSet($object);

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
            $this->maxPositions[$oldHash] = $this->getMaxPosition($em, $meta, $config, $object, $oldGroups);
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
            $this->maxPositions[$hash] = $this->getMaxPosition($em, $meta, $config, $object);
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
        $uow->recomputeSingleEntityChangeSet($meta, $object);
    }

    /**
     * Computes node positions and updates the sort field in memory and in the db
     * @param object $em ObjectManager
     */
    private function processDeletion($em, $config, $meta, $object)
    {
        $position = $meta->getReflectionProperty($config['position'])->getValue($object);

        // Get groups
        $groups = $this->getGroups($meta, $config, $object);

        // Get hash
        $hash = $this->getHash($meta, $groups, $object, $config);

        // Get max position
        if (!isset($this->maxPositions[$hash])) {
            $this->maxPositions[$hash] = $this->getMaxPosition($em, $meta, $config, $object);
        }

        // Add relocation
        $this->addRelocation($hash, $config['useObjectClass'], $groups, $position, -1, -1);
    }

    private function processRelocations($em)
    {
        foreach ($this->relocations as $hash => $relocation) {
            $config = $this->getConfiguration($em, $relocation['name']);
            foreach ($relocation['deltas'] as $delta) {
                if ($delta['start'] > $this->maxPositions[$hash] || $delta['delta'] == 0) {
                    continue;
                }
                $sign = $delta['delta'] < 0 ? "-" : "+";
                $absDelta = abs($delta['delta']);
                $dql = "UPDATE {$relocation['name']} n";
                $dql .= " SET n.{$config['position']} = n.{$config['position']} {$sign} {$absDelta}";
                $dql .= " WHERE n.{$config['position']} >= {$delta['start']}";
                // if not null, false or 0
                if ($delta['stop'] > 0) {
                    $dql .= " AND n.{$config['position']} < {$delta['stop']}";
                }
                $i = -1;
                $params = array();
                foreach ($relocation['groups'] as $group => $value) {
                    if (is_null($value)) {
                        $dql .= " AND n.{$group} IS NULL";
                    } else {
                        $dql .= " AND n.{$group} = :val___".(++$i);
                        $params['val___'.$i] = $value;
                    }
                }
                $q = $em->createQuery($dql);
                $q->setParameters($params);
                $q->getSingleScalarResult();
                $meta = $em->getClassMetadata($relocation['name']);

                // now walk through the unit of work in memory objects and sync those
                $uow = $em->getUnitOfWork();
                foreach ($uow->getIdentityMap() as $className => $objects) {
                    // for inheritance mapped classes, only root is always in the identity map
                    if ($className !== $meta->rootEntityName || !$this->getConfiguration($em, $className)) {
                        continue;
                    }
                    foreach ($objects as $object) {
                        if ($object instanceof Proxy && !$object->__isInitialized__) {
                            continue;
                        }

                        // if the entity's position is already changed, stop now
                        if (array_key_exists($config['position'], $uow->getEntityChangeSet($object))) {
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
                            $em->getUnitOfWork()->setOriginalEntityProperty($oid, $config['position'], $pos + $delta['delta']);
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

    private function getMaxPosition($em, $meta, $config, $object, array $groups = array())
    {
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
            if (is_object($val) && ($uow->isScheduledForInsert($val) || !$em->getMetadataFactory()->isTransient(ClassUtils::getClass($val)) && UnitOfWork::STATE_MANAGED !== $uow->getEntityState($val))) {
                return -1;
            }
        }

        $qb = $em->createQueryBuilder();
        $qb->select('MAX(n.'.$config['position'].')')
           ->from($config['useObjectClass'], 'n');
        $qb = $this->addGroupWhere($qb, $groups);
        $query = $qb->getQuery();
        $query->useQueryCache(false);
        $query->useResultCache(false);
        $res = $query->getResult();
        $maxPos = $res[0][1];
        if (is_null($maxPos)) $maxPos = -1;
        return intval($maxPos);
    }

    private function addGroupWhere($qb, $groups)
    {
        $i = 1;
        foreach ($groups as $group => $value) {
            $whereFunc = is_null($qb->getDQLPart('where')) ? 'where' : 'andWhere';
            if (is_null($value)) {
                $qb->{$whereFunc}($qb->expr()->isNull('n.'.$group));
            } else {
                $qb->{$whereFunc}('n.'.$group.' = :group__'.$i);
                $qb->setParameter('group__'.$i, $value);
            }
            $i++;
        }
        return $qb;
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
