<?php

namespace Gedmo\Sortable;

use Gedmo\Mapping\MappedEventSubscriber;
use Gedmo\Mapping\ObjectManagerHelper as OMH;
use Gedmo\Sortable\Mapping\SortableMetadata;
use Doctrine\Common\EventArgs;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

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
        );
    }

    /**
     * Maps additional metadata
     *
     * @param EventArgs $event
     */
    public function loadClassMetadata(EventArgs $event)
    {
        $this->loadMetadataForObjectClass(OMH::getObjectManagerFromEvent($event), $event->getClassMetadata());
    }

    /**
     * Update position on objects being updated during flush
     * if they require changing
     *
     * @param EventArgs $event
     */
    public function onFlush(EventArgs $event)
    {
        $om = OMH::getObjectManagerFromEvent($event);
        $uow = $om->getUnitOfWork();

        // process all objects being deleted
        foreach (OMH::getScheduledObjectDeletions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($exm = $this->getConfiguration($om, $meta->name)) {
                $this->processDeletion($om, $exm, $meta, $object);
            }
        }

        // process all objects being updated
        foreach (OMH::getScheduledObjectUpdates($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($exm = $this->getConfiguration($om, $meta->name)) {
                $this->processUpdate($om, $exm, $meta, $object);
            }
        }

        // process all objects being inserted
        foreach (OMH::getScheduledObjectInsertions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($exm = $this->getConfiguration($om, $meta->name)) {
                $this->processInsert($om, $exm, $meta, $object);
            }
        }
        $this->processRelocations($om);
    }

    /**
     * Computes node positions and updates the sort field in memory and in the db
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     * @param \Gedmo\Sortable\Mapping\SortableMetadata $exm
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $meta
     * @param Object $object
     */
    private function processInsert(ObjectManager $om, SortableMetadata $exm, ClassMetadata $meta, $object)
    {
        $uow = $om->getUnitOfWork();
        $changed = false;

        foreach ($exm->getFields() as $position) {
            $options = $exm->getOptions($position);
            $old = $new = $meta->getReflectionProperty($position)->getValue($object);

            if (is_null($new)) {
                $new = -1;
            }

            // Get groups
            $groups = $this->getGroups($meta, $options['groups'], $object);
            // Get hash
            $hash = $this->getHash($options['rootClass'], $position, $groups);
            // Get max position
            $max = $this->getMaxPosition($om, $position, $object, $groups);

            // Compute position if it is negative
            if ($new < 0) {
                $new += $max + 2; // position == -1 => append at end of list
                if ($new < 0) $new = 0;
            }

            // Set position to max position if it is too big
            $new = min(array($max + 1, $new));

            // Compute relocations
            $relocation = array($hash, $options['rootClass'], $position, $groups, $new, -1, +1);

            // Apply existing relocations
            $applyDelta = 0;
            if (isset($this->relocations[$hash])) {
                foreach ($this->relocations[$hash]['deltas'] as $delta) {
                    if ($delta['start'] <= $new
                            && ($delta['stop'] > $new || $delta['stop'] < 0)) {
                        $applyDelta += $delta['delta'];
                    }
                }
            }
            $new += $applyDelta;

            // Add relocations
            call_user_func_array(array($this, 'addRelocation'), $relocation);
            // Set new position
            if ($old < 0 || is_null($old)) {
                $meta->getReflectionProperty($position)->setValue($object, $new);
                $changed = true;
            }
        }
        $changed && OMH::recomputeSingleObjectChangeSet($uow, $meta, $object);
    }

    /**
     * Computes node positions and updates the sort field in memory and in the db
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     * @param \Gedmo\Sortable\Mapping\SortableMetadata $exm
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $meta
     * @param Object $object
     */
    private function processUpdate(ObjectManager $om, SortableMetadata $exm, ClassMetadata $meta, $object)
    {
        $uow = $om->getUnitOfWork();
        $changeSet = OMH::getObjectChangeSet($uow, $object);
        $recompute = false;

        foreach ($exm->getFields() as $position) {
            $options = $exm->getOptions($position);
            $changed = false;
            // Get groups
            $groups = $this->getGroups($meta, $options['groups'], $object);
            // handle old groups
            foreach (array_keys($groups) as $group) {
                if (array_key_exists($group, $changeSet)) {
                    $changed = true;

                    $oldGroups = array($group => $changeSet[$group][0]);
                    $oldHash = $this->getHash($options['rootClass'], $position, $oldGroups);
                    $oldMax = $this->getMaxPosition($om, $position, $object, $oldGroups);
                    $this->addRelocation(
                        $oldHash,
                        $options['rootClass'],
                        $position,
                        $oldGroups,
                        $meta->getReflectionProperty($position)->getValue($object) + 1,
                        $oldMax + 1, -1, true
                    );
                }
            }

            if (array_key_exists($position, $changeSet)) {
                // position was manually updated
                list($oldPosition, $newPosition) = $changeSet[$position];
                $changed = $changed || $oldPosition != $newPosition;
            } elseif ($changed) {
                // group has changed, so position has to be recalculated
                $oldPosition = -1;
                $newPosition = -1;
                // specific case
            }
            if (!$changed) return;

            // Get hash
            $hash = $this->getHash($options['rootClass'], $position, $groups);
            // Get max position
            $max = $this->getMaxPosition($om, $position, $object, $groups);
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
                $relocation = array($hash, $options['rootClass'], $position, $groups, $newPosition, -1, +1);
            } elseif ($newPosition < $oldPosition) {
                $relocation = array($hash, $options['rootClass'], $position, $groups, $newPosition, $oldPosition, +1);
            } elseif ($newPosition > $oldPosition) {
                $relocation = array($hash, $options['rootClass'], $position, $groups, $oldPosition + 1, $newPosition + 1, -1);
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
            $meta->getReflectionProperty($position)->setValue($object, $newPosition);
            $recompute = true;
        }
        $recompute && OMH::recomputeSingleObjectChangeSet($uow, $meta, $object);
    }

    /**
     * Computes node positions and updates the sort field in memory and in the db
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     * @param \Gedmo\Sortable\Mapping\SortableMetadata $exm
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $meta
     * @param Object $object
     */
    private function processDeletion(ObjectManager $om, SortableMetadata $exm, ClassMetadata $meta, $object)
    {
        foreach ($exm->getFields() as $position) {
            $options = $exm->getOptions($position);
            $pos = $meta->getReflectionProperty($position)->getValue($object);
            // Get groups
            $groups = $this->getGroups($meta, $options['groups'], $object);
            // Get hash
            $hash = $this->getHash($options['rootClass'], $position, $groups);
            // Get max position
            $max = $this->getMaxPosition($om, $position, $object, $groups);
            // Add relocation
            $this->addRelocation($hash, $options['rootClass'], $position, $groups, $pos, -1, -1);
        }
    }

    /**
     * Execute relocations
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     */
    private function processRelocations(ObjectManager $om)
    {
        foreach ($this->relocations as $hash => $relocation) {
            $exm = $this->getConfiguration($om, $relocation['rootClass']);
            $meta = $om->getClassMetadata($relocation['rootClass']);
            foreach ($relocation['deltas'] as $delta) {
                if ($delta['start'] > $this->maxPositions[$hash] || $delta['delta'] == 0) {
                    continue;
                }
                $sign = $delta['delta'] < 0 ? "-" : "+";
                $absDelta = abs($delta['delta']);
                if ($om instanceof EntityManager) {
                    $dql = "UPDATE {$relocation['rootClass']} n";
                    $dql .= " SET n.{$relocation['position']} = n.{$relocation['position']} {$sign} {$absDelta}";
                    $dql .= " WHERE n.{$relocation['position']} >= {$delta['start']}";
                    // if not null, false or 0
                    if ($delta['stop'] > 0) {
                        $dql .= " AND n.{$relocation['position']} < {$delta['stop']}";
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
                    $q = $om->createQuery($dql);
                    $q->setParameters($params);
                    $q->getSingleScalarResult();
                } elseif ($om instanceof DocumentManager) {
                    $qb = $om->createQueryBuilder($relocation['rootClass']);
                    $cond = "this.{$relocation['position']} >= {$delta['start']}";
                    if ($delta['stop'] > 0) {
                        $cond .= " && this.{$relocation['position']} < {$delta['stop']}";
                    }
                    foreach ($relocation['groups'] as $group => $value) {
                        if ($meta->isSingleValuedAssociation($group) && null !== $value) {
                            $id = OMH::getIdentifier($om, $value);
                            $qb->field($group . '.$id')->equals(new \MongoId($id));
                        } else {
                            $qb->field($group)->equals($value);
                        }
                    }
                    $qb->where("function() { return {$cond}; }");
                    if ($cursor = $qb->getQuery()->execute()) {
                        foreach ($cursor as $object) {
                            $id = OMH::getIdentifier($om, $object);
                            $pos = $meta->getReflectionProperty($relocation['position'])->getValue($object);
                            $om->createQueryBuilder($relocation['rootClass'])
                                ->update()
                                ->field($relocation['position'])->set($pos + $delta['delta'])
                                ->field($meta->identifier)->equals($id)
                                ->getQuery()
                                ->execute();
                        }
                    }
                }

                // now walk through the unit of work in memory objects and sync those
                $uow = $om->getUnitOfWork();
                foreach ($uow->getIdentityMap() as $className => $objects) {
                    // for inheritance mapped classes, only root is always in the identity map
                    if ($className !== OMH::getRootObjectClass($meta) || !$this->getConfiguration($om, $className)) {
                        continue;
                    }
                    foreach ($objects as $object) {
                        if (OMH::isUninitializedProxy($object)) {
                            continue;
                        }

                        // if the entity's position is already changed, stop now
                        if (array_key_exists($relocation['position'], OMH::getObjectChangeSet($uow, $object))) {
                            continue;
                        }

                        $oid = spl_object_hash($object);
                        $pos = $meta->getReflectionProperty($relocation['position'])->getValue($object);
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
                            $meta->getReflectionProperty($relocation['position'])->setValue($object, $pos + $delta['delta']);
                            OMH::setOriginalObjectProperty($uow, $oid, $relocation['position'], $pos + $delta['delta']);
                        }
                    }
                }
            }
        }

        // Clear relocations
        $this->relocations = array();
        $this->maxPositions = array();
    }

    /**
     * Get specific hash key
     *
     * @param string $rootClass
     * @param string $position - sortable position field
     * @param array $groups
     * @return string
     */
    private function getHash($rootClass, $position, array $groups)
    {
        $data = $rootClass . $position;
        foreach ($groups as $group => $val) {
            if ($val instanceof \DateTime) {
                $val = $val->format('c');
            } elseif (is_object($val)) {
                $val = spl_object_hash($val);
            }
            $data .= $group.$val;
        }
        return md5($data);
    }

    /**
     * Computes node positions and updates the sort field in memory and in the db
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     * @param string $position - sortable position field
     * @param Object $object
     * @param array $groups - group data
     * @return integer
     */
    private function getMaxPosition(ObjectManager $om, $position, $object, array $groups = array())
    {
        $uow = $om->getUnitOfWork();
        $meta = $om->getClassMetadata(get_class($object));
        $exm = $this->getConfiguration($om, $meta->name);
        $options = $exm->getOptions($position);
        $maxPos = null;
        $groups = $groups ?: $this->getGroups($meta, $options['groups'], $object);
        $hash = $this->getHash($options['rootClass'], $position, $groups);

        // Check for cached max position
        if (isset($this->maxPositions[$hash])) {
            return $this->maxPositions[$hash];
        }

        // Check for groups that are associations. If the value is an object and is
        // scheduled for insert, it has no identifier yet and is obviously new
        // see issue #226
        foreach ($groups as $group => $val) {
            if (is_object($val) && $uow->isScheduledForInsert($val)) {
                return $this->maxPositions[$hash] = -1;
            }
        }

        if ($om instanceof EntityManager) {
            $qb = $om->createQueryBuilder();
            $qb->select('MAX(n.'.$position.')')
               ->from($options['rootClass'], 'n');
            $qb = $this->addGroupWhere($qb, $groups);
            $query = $qb->getQuery();
            $query->useQueryCache(false);
            $query->useResultCache(false);
            $res = $query->getResult();
            $maxPos = $res[0][1];
        } elseif ($om instanceof DocumentManager) {
            $qb = $om->createQueryBuilder($options['rootClass']);
            $qb->select($position);
            $qb->limit(1);
            $qb->hydrate(false);
            $qb->sort($position, 'desc');
            foreach ($groups as $group => $value) {
                if ($meta->isSingleValuedAssociation($group) && null !== $value) {
                    $id = OMH::getIdentifier($om, $value);
                    $qb->field($group . '.$id')->equals(new \MongoId($id));
                } else {
                    $qb->field($group)->equals($value);
                }
            }
            if ($cursor = $qb->getQuery()->execute()) {
                foreach ($cursor as $item) {
                    $maxPos = $item[$position]; break;
                }
            }
        }
        return $this->maxPositions[$hash] = is_null($maxPos) ? -1 : intval($maxPos);
    }

    private function addGroupWhere(QueryBuilder $qb, array $groups)
    {
        $i = 1;
        foreach ($groups as $group => $value) {
            $whereFunc = is_null($qb->getDQLPart('where')) ? 'where' : 'andWhere';
            if (is_null($value)) {
                $qb->{$whereFunc}($qb->expr()->isNull('n.'.$group));
            } else {
                $qb->{$whereFunc}('n.'.$group.' = :group__'.$i);
                $qb->setParameter('group__'.($i++), $value);
            }
        }
        return $qb;
    }

    /**
     * Add a relocation rule
     *
     * @param string $hash - The hash of the sorting group
     * @param string $rootClass - The object class
     * @param string $position - sortable position field
     * @param array $groups - The sorting groups
     * @param int $start - Inclusive index to start relocation from
     * @param int $stop - Exclusive index to stop relocation at
     * @param int $delta - The delta to add to relocated nodes
     */
    private function addRelocation($hash, $rootClass, $position, $groups, $start, $stop, $delta)
    {
        if (!array_key_exists($hash, $this->relocations)) {
            $this->relocations[$hash] = array(
                'rootClass' => $rootClass,
                'groups' => $groups,
                'position' => $position,
                'deltas' => array()
            );
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
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $meta
     * @param array $options - sortable position field options
     * @param Object $object
     *
     * @return array
     */
    private function getGroups(ClassMetadata $meta, array $fields, $object)
    {
        $groups = array();
        foreach ($fields as $field) {
            $groups[$field] = $meta->getReflectionProperty($field)->getValue($object);
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
