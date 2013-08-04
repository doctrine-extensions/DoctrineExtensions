<?php

namespace Gedmo\Tree\Strategy\ORM;

use Gedmo\Exception\UnexpectedValueException;
use Gedmo\Mapping\ObjectManagerHelper as OMH;
use Gedmo\Tree\Strategy;
use Gedmo\Tree\TreeListener;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
 * This strategy makes the tree act like a nested set.
 *
 * This behavior can impact the performance of your application
 * since nested set trees are slow on inserts and updates.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Nested implements Strategy
{
    /**
     * Previous sibling position
     */
    const PREV_SIBLING = 'PrevSibling';

    /**
     * Next sibling position
     */
    const NEXT_SIBLING = 'NextSibling';

    /**
     * Last child position
     */
    const LAST_CHILD = 'LastChild';

    /**
     * First child position
     */
    const FIRST_CHILD = 'FirstChild';

    /**
     * TreeListener
     *
     * @var AbstractTreeListener
     */
    protected $listener = null;

    /**
     * The max number of "right" field of the
     * tree in case few root nodes will be persisted
     * on one flush for node classes
     *
     * @var array
     */
    private $treeEdges = array();

    /**
     * Stores a list of node position strategies
     * for each node by object hash
     *
     * @var array
     */
    private $nodePositions = array();

    /**
     * Stores a list of delayed nodes for correct order of updates
     *
     * @var array
     */
    private $delayedNodes = array();

    /**
     * {@inheritdoc}
     */
    public function __construct(TreeListener $listener)
    {
        $this->listener = $listener;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Strategy::NESTED;
    }

    /**
     * Set node position strategy
     *
     * @param string $oid
     * @param string $position
     */
    public function setNodePosition($oid, $position)
    {
        $valid = array(
            self::FIRST_CHILD,
            self::LAST_CHILD,
            self::NEXT_SIBLING,
            self::PREV_SIBLING
        );
        if (!in_array($position, $valid, false)) {
            throw new \Gedmo\Exception\InvalidArgumentException("Position: {$position} is not valid in nested set tree");
        }
        $this->nodePositions[$oid] = $position;
    }

    /**
     * {@inheritdoc}
     */
    public function processScheduledInsertion(ObjectManager $em, $node)
    {
        $meta = $em->getClassMetadata(get_class($node));
        $tree = $this->listener->getConfiguration($em, $meta->name)->getMapping();

        $meta->getReflectionProperty($tree['left'])->setValue($node, 0);
        $meta->getReflectionProperty($tree['right'])->setValue($node, 0);
        if (isset($tree['level'])) {
            $meta->getReflectionProperty($tree['level'])->setValue($node, 0);
        }
        if (isset($tree['root'])) {
            $meta->getReflectionProperty($tree['root'])->setValue($node, 0);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processScheduledUpdate(ObjectManager $em, $node)
    {
        $meta = $em->getClassMetadata(get_class($node));
        $tree = $this->listener->getConfiguration($em, $meta->name)->getMapping();
        $uow = $em->getUnitOfWork();

        $changeSet = $uow->getEntityChangeSet($node);
        if (isset($tree['root']) && isset($changeSet[$tree['root']])) {
            throw new UnexpectedValueException("Root cannot be changed manualy, change parent instead");
        }

        $oid = spl_object_hash($node);
        if (isset($changeSet[$tree['left']]) && isset($this->nodePositions[$oid])) {
            $parent = $meta->getReflectionProperty($tree['parent'])->getValue($node);
            // revert simulated changeset
            $uow->clearEntityChangeSet($oid);
            $meta->getReflectionProperty($tree['left'])->setValue($node, $changeSet[$tree['left']][0]);
            $uow->setOriginalEntityProperty($oid, $tree['left'], $changeSet[$tree['left']][0]);
            // set back all other changes
            foreach ($changeSet as $field => $set) {
                if ($field !== $tree['left']) {
                    if (is_array($set) && array_key_exists(0, $set) && array_key_exists(1, $set)) {
                        $uow->setOriginalEntityProperty($oid, $field, $set[0]);
                        $meta->getReflectionProperty($field)->setValue($node, $set[1]);
                    } else {
                        $uow->setOriginalEntityProperty($oid, $field, $set);
                        $meta->getReflectionProperty($field)->setValue($node, $set);
                    }
                }
            }
            $uow->recomputeSingleEntityChangeSet($meta, $node);
            $this->updateNode($em, $node, $parent);
        } elseif (isset($changeSet[$tree['parent']])) {
            $this->updateNode($em, $node, $changeSet[$tree['parent']][1]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processPostPersist(ObjectManager $em, $node)
    {
        $meta = $em->getClassMetadata(get_class($node));
        $tree = $this->listener->getConfiguration($em, $meta->name)->getMapping();
        $parent = $meta->getReflectionProperty($tree['parent'])->getValue($node);
        $this->updateNode($em, $node, $parent, self::LAST_CHILD);
    }

    /**
     * {@inheritdoc}
     */
    public function processScheduledDelete(ObjectManager $em, $node)
    {
        $meta = $em->getClassMetadata(get_class($node));
        $tree = $this->listener->getConfiguration($em, $meta->name)->getMapping();
        $uow = $em->getUnitOfWork();

        $leftValue = $meta->getReflectionProperty($tree['left'])->getValue($node);
        $rightValue = $meta->getReflectionProperty($tree['right'])->getValue($node);

        if (!$leftValue || !$rightValue) {
            return;
        }
        $rootId = isset($tree['root']) ? $meta->getReflectionProperty($tree['root'])->getValue($node) : null;
        $diff = $rightValue - $leftValue + 1;
        if ($diff > 2) {
            $qb = $em->createQueryBuilder();
            $qb->select('node')
                ->from($tree['rootClass'], 'node')
                ->where($qb->expr()->between('node.'.$tree['left'], '?1', '?2'))
                ->setParameters(array(1 => $leftValue, 2 => $rightValue))
            ;

            if (isset($tree['root'])) {
                $qb->andWhere($rootId === null ?
                    $qb->expr()->isNull('node.'.$tree['root']) :
                    $qb->expr()->eq('node.'.$tree['root'], is_string($rootId) ? $qb->expr()->literal($rootId) : $rootId)
                );
            }
            $q = $qb->getQuery();
            // get nodes for deletion
            $nodes = $q->getResult();
            foreach ((array)$nodes as $removalNode) {
                $uow->scheduleForDelete($removalNode);
            }
        }
        $this->shiftRL($em, $meta->name, $rightValue + 1, -$diff, $rootId);
    }

    /**
     * {@inheritdoc}
     */
    public function onFlushEnd(ObjectManager $em)
    {
        // reset values
        $this->treeEdges = array();
        $this->updatesOnNodeClasses = array();
    }

    /**
     * {@inheritdoc}
     */
    public function processPreRemove(ObjectManager $em, $node)
    {}

    /**
     * {@inheritdoc}
     */
    public function processPrePersist(ObjectManager $em, $node)
    {}

    /**
     * {@inheritdoc}
     */
    public function processPreUpdate(ObjectManager $em, $node)
    {}

    /**
     * {@inheritdoc}
     */
    public function processMetadataLoad(ObjectManager $em, ClassMetadata $meta)
    {}

    /**
     * {@inheritdoc}
     */
    public function processPostUpdate(ObjectManager $em, $entity)
    {}

    /**
     * {@inheritdoc}
     */
    public function processPostRemove(ObjectManager $em, $entity)
    {}

    /**
     * Update the $node with a diferent $parent
     * destination
     *
     * @param EntityManager $em
     * @param object $node - target node
     * @param object $parent - destination node
     * @param string $position
     * @throws Gedmo\Exception\UnexpectedValueException
     * @return void
     */
    public function updateNode(EntityManager $em, $node, $parent, $position = 'FirstChild')
    {
        $em->initializeObject($node);
        $meta = $em->getClassMetadata(get_class($node));
        $tree = $this->listener->getConfiguration($em, $meta->name)->getMapping();

        $rootId = isset($tree['root']) ? $meta->getReflectionProperty($tree['root'])->getValue($node) : null;
        $identifierField = $meta->getSingleIdentifierFieldName();
        $nodeId = $meta->getReflectionProperty($identifierField)->getValue($node);

        $left = $meta->getReflectionProperty($tree['left'])->getValue($node);
        $right = $meta->getReflectionProperty($tree['right'])->getValue($node);

        $isNewNode = empty($left) && empty($right);
        if ($isNewNode) {
            $left = 1;
            $right = 2;
        }

        $oid = spl_object_hash($node);
        if (isset($this->nodePositions[$oid])) {
            $position = $this->nodePositions[$oid];
        }
        $level = 0;
        $treeSize = $right - $left + 1;
        $newRootId = null;
        if ($parent) {
            $em->initializeObject($parent);
            $parentRootId = isset($tree['root']) ? $meta->getReflectionProperty($tree['root'])->getValue($parent) : null;
            $parentOid = spl_object_hash($parent);
            $parentLeft = $meta->getReflectionProperty($tree['left'])->getValue($parent);
            $parentRight = $meta->getReflectionProperty($tree['right'])->getValue($parent);
            if (empty($parentLeft) && empty($parentRight)) {
                // parent node is a new node, but wasn't processed yet (due to Doctrine commit order calculator redordering)
                // We delay processing of node to the moment parent node will be processed
                if (!isset($this->delayedNodes[$parentOid])) {
                    $this->delayedNodes[$parentOid] = array();
                }
                $this->delayedNodes[$parentOid][] = array('node' => $node, 'position' => $position);
                return;
            }
            if (!$isNewNode && $rootId === $parentRootId && $parentLeft >= $left && $parentRight <= $right) {
                throw new UnexpectedValueException("Cannot set child as parent to node: {$nodeId}");
            }
            if (isset($tree['level'])) {
                $level = $meta->getReflectionProperty($tree['level'])->getValue($parent);
            }
            switch ($position) {
                case self::PREV_SIBLING:
                    if (property_exists($node, 'sibling')) {
                        $em->initializeObject($node->sibling);
                        $start = $meta->getReflectionProperty($tree['left'])->getValue($node->sibling);
                        $level++;
                    } else {
                        $newParent = $meta->getReflectionProperty($tree['parent'])->getValue($parent);
                        if (is_null($newParent) && (isset($tree['root']) || $isNewNode)) {
                            throw new UnexpectedValueException("Cannot persist sibling for a root node, tree operation is not possible");
                        }
                        $meta->getReflectionProperty($tree['parent'])->setValue($node, $newParent);
                        $em->getUnitOfWork()->recomputeSingleEntityChangeSet($meta, $node);
                        $start = $parentLeft;
                    }
                    break;

                case self::NEXT_SIBLING:
                    if (property_exists($node, 'sibling')) {
                        $em->initializeObject($node->sibling);
                        $start = $meta->getReflectionProperty($tree['right'])->getValue($node->sibling) + 1;
                        $level++;
                    } else {
                        $newParent = $meta->getReflectionProperty($tree['parent'])->getValue($parent);
                        if (is_null($newParent) && (isset($tree['root']) || $isNewNode)) {
                            throw new UnexpectedValueException("Cannot persist sibling for a root node, tree operation is not possible");
                        }
                        $meta->getReflectionProperty($tree['parent'])->setValue($node, $newParent);
                        $em->getUnitOfWork()->recomputeSingleEntityChangeSet($meta, $node);
                        $start = $parentRight + 1;
                    }
                    break;

                case self::LAST_CHILD:
                    $start = $parentRight;
                    $level++;
                    break;

                case self::FIRST_CHILD:
                default:
                    $start = $parentLeft + 1;
                    $level++;
                    break;
            }
            $this->shiftRL($em, $tree['rootClass'], $start, $treeSize, $parentRootId);
            if (!$isNewNode && $rootId === $parentRootId && $left >= $start) {
                $left += $treeSize;
                $meta->getReflectionProperty($tree['left'])->setValue($node, $left);
            }
            if (!$isNewNode && $rootId === $parentRootId && $right >= $start) {
                $right += $treeSize;
                $meta->getReflectionProperty($tree['right'])->setValue($node, $right);
            }
            $newRootId = $parentRootId;
        } elseif (!isset($tree['root'])) {
            $start = isset($this->treeEdges[$tree['rootClass']]) ?
                $this->treeEdges[$tree['rootClass']] : $this->max($em, $tree['rootClass']);
            $this->treeEdges[$tree['rootClass']] = $start + 2;
            $start++;
        } else {
            $start = 1;
            $newRootId = $nodeId;
        }

        $diff = $start - $left;
        if (!$isNewNode) {
            $levelDiff = isset($tree['level']) ? $level - $meta->getReflectionProperty($tree['level'])->getValue($node) : null;
            $this->shiftRangeRL(
                $em,
                $tree['rootClass'],
                $left,
                $right,
                $diff,
                $rootId,
                $newRootId,
                $levelDiff
            );
            $this->shiftRL($em, $tree['rootClass'], $left, -$treeSize, $rootId);
        } else {
            $qb = $em->createQueryBuilder();
            $qb->update($tree['rootClass'], 'node');
            if (isset($tree['root'])) {
                $qb->set('node.'.$tree['root'], null === $newRootId ?
                    'NULL' :
                    (is_string($newRootId) ? $qb->expr()->literal($newRootId) : $newRootId)
                );
                $meta->getReflectionProperty($tree['root'])->setValue($node, $newRootId);
                $em->getUnitOfWork()->setOriginalEntityProperty($oid, $tree['root'], $newRootId);
            }
            if (isset($tree['level'])) {
                $qb->set('node.' . $tree['level'], $level);
                $meta->getReflectionProperty($tree['level'])->setValue($node, $level);
                $em->getUnitOfWork()->setOriginalEntityProperty($oid, $tree['level'], $level);
            }
            if (isset($newParent)) {
                $newParentId = $meta->getReflectionProperty($identifierField)->getValue($newParent);
                $qb->set('node.'.$tree['parent'], null === $newParentId ?
                    'NULL' :
                    (is_string($newParentId) ? $qb->expr()->literal($newParentId) : $newParentId)
                );
                $meta->getReflectionProperty($tree['parent'])->setValue($node, $newParent);
                $em->getUnitOfWork()->setOriginalEntityProperty($oid, $tree['parent'], $newParent);
            }
            $qb->set('node.' . $tree['left'], $left + $diff);
            $qb->set('node.' . $tree['right'], $right + $diff);
            // node id cannot be null
            $qb->where($qb->expr()->eq('node.'.$identifierField, is_string($nodeId) ? $qb->expr()->literal($nodeId) : $nodeId));
            $qb->getQuery()->getSingleScalarResult();
            $meta->getReflectionProperty($tree['left'])->setValue($node, $left + $diff);
            $meta->getReflectionProperty($tree['right'])->setValue($node, $right + $diff);
            $em->getUnitOfWork()->setOriginalEntityProperty($oid, $tree['left'], $left + $diff);
            $em->getUnitOfWork()->setOriginalEntityProperty($oid, $tree['right'], $right + $diff);
        }
        if (isset($this->delayedNodes[$oid])) {
            foreach($this->delayedNodes[$oid] as $nodeData) {
                $this->updateNode($em, $nodeData['node'], $node, $nodeData['position']);
            }
        }
    }

    /**
     * Get the edge of tree
     *
     * @param EntityManager $em
     * @param string $class
     * @param integer $rootId
     * @return integer
     */
    public function max(EntityManager $em, $class, $rootId = 0)
    {
        $meta = $em->getClassMetadata($class);
        $tree = $this->listener->getConfiguration($em, $meta->name)->getMapping();
        $qb = $em->createQueryBuilder();
        $qb->select($qb->expr()->max('node.'.$tree['right']))
            ->from($tree['rootClass'], 'node')
        ;

        if (isset($tree['root']) && $rootId) {
            $qb->where($rootId === null ?
                $qb->expr()->isNull('node.'.$tree['root']) :
                $qb->expr()->eq('node.'.$tree['root'], is_string($rootId) ? $qb->expr()->literal($rootId) : $rootId)
            );
        }
        $query = $qb->getQuery();
        $right = $query->getSingleScalarResult();
        return intval($right);
    }

    /**
     * Shift tree left and right values by delta
     *
     * @param EntityManager $em
     * @param string $class
     * @param integer $first
     * @param integer $delta
     * @param integer|string $rootId
     * @return void
     */
    public function shiftRL(EntityManager $em, $class, $first, $delta, $rootId = null)
    {
        $meta = $em->getClassMetadata($class);
        $tree = $this->listener->getConfiguration($em, $meta->name)->getMapping();

        $sign = ($delta >= 0) ? ' + ' : ' - ';
        $absDelta = abs($delta);
        $qb = $em->createQueryBuilder();
        $qb->update($tree['rootClass'], 'node')
            ->set('node.'.$tree['left'], "node.{$tree['left']} {$sign} {$absDelta}")
            ->where($qb->expr()->gte('node.'.$tree['left'], $first))
        ;
        if (isset($tree['root'])) {
            $qb->andWhere($rootId === null ?
                $qb->expr()->isNull('node.'.$tree['root']) :
                $qb->expr()->eq('node.'.$tree['root'], is_string($rootId) ? $qb->expr()->literal($rootId) : $rootId)
            );
        }
        $qb->getQuery()->getSingleScalarResult();

        $qb = $em->createQueryBuilder();
        $qb->update($tree['rootClass'], 'node')
            ->set('node.'.$tree['right'], "node.{$tree['right']} {$sign} {$absDelta}")
            ->where($qb->expr()->gte('node.'.$tree['right'], $first))
        ;
        if (isset($tree['root'])) {
            $qb->andWhere($rootId === null ?
                $qb->expr()->isNull('node.'.$tree['root']) :
                $qb->expr()->eq('node.'.$tree['root'], is_string($rootId) ? $qb->expr()->literal($rootId) : $rootId)
            );
        }

        $qb->getQuery()->getSingleScalarResult();
        // update in memory nodes increases performance, saves some IO
        foreach ($em->getUnitOfWork()->getIdentityMap() as $className => $nodes) {
            // for inheritance mapped classes, only root is always in the identity map
            if ($className !== $meta->rootEntityName) {
                continue;
            }
            foreach ($nodes as $node) {
                if (OMH::isUninitializedProxy($node)) {
                    continue;
                }
                $oid = spl_object_hash($node);
                $left = $meta->getReflectionProperty($tree['left'])->getValue($node);
                $root = isset($tree['root']) ? $meta->getReflectionProperty($tree['root'])->getValue($node) : null;
                if ($root === $rootId && $left >= $first) {
                    $meta->getReflectionProperty($tree['left'])->setValue($node, $left + $delta);
                    $em->getUnitOfWork()->setOriginalEntityProperty($oid, $tree['left'], $left + $delta);
                }
                $right = $meta->getReflectionProperty($tree['right'])->getValue($node);
                if ($root === $rootId && $right >= $first) {
                    $meta->getReflectionProperty($tree['right'])->setValue($node, $right + $delta);
                    $em->getUnitOfWork()->setOriginalEntityProperty($oid, $tree['right'], $right + $delta);
                }
            }
        }
    }

    /**
     * Shift range of right and left values on tree
     * depending on tree level diference also
     *
     * @param EntityManager $em
     * @param string $class
     * @param integer $first
     * @param integer $last
     * @param integer $delta
     * @param integer|string $rootId
     * @param integer|string $destRootId
     * @param integer $levelDelta
     * @return void
     */
    public function shiftRangeRL(EntityManager $em, $class, $first, $last, $delta, $rootId = null, $destRootId = null, $levelDelta = null)
    {
        $meta = $em->getClassMetadata($class);
        $tree = $this->listener->getConfiguration($em, $meta->name)->getMapping();

        $sign = ($delta >= 0) ? ' + ' : ' - ';
        $absDelta = abs($delta);
        $levelSign = ($levelDelta >= 0) ? ' + ' : ' - ';
        $absLevelDelta = abs($levelDelta);

        $qb = $em->createQueryBuilder();
        $qb->update($tree['rootClass'], 'node')
            ->set('node.'.$tree['left'], "node.{$tree['left']} {$sign} {$absDelta}")
            ->set('node.'.$tree['right'], "node.{$tree['right']} {$sign} {$absDelta}")
            ->where($qb->expr()->gte('node.'.$tree['left'], $first))
            ->andWhere($qb->expr()->lte('node.'.$tree['right'], $last))
        ;
        if (isset($tree['root'])) {
            $qb->set(
                'node.'.$tree['root'],
                is_string($destRootId) ? $qb->expr()->literal($destRootId) : $destRootId
            );
            $qb->andWhere($rootId === null ?
                $qb->expr()->isNull('node.'.$tree['root']) :
                $qb->expr()->eq('node.'.$tree['root'], is_string($rootId) ? $qb->expr()->literal($rootId) : $rootId)
            );
        }
        if (isset($tree['level'])) {
            $qb->set('node.'.$tree['level'], "node.{$tree['level']} {$levelSign} {$absLevelDelta}");
        }
        $qb->getQuery()->getSingleScalarResult();
        // update in memory nodes increases performance, saves some IO
        foreach ($em->getUnitOfWork()->getIdentityMap() as $className => $nodes) {
            // for inheritance mapped classes, only root is always in the identity map
            if ($className !== $meta->rootEntityName) {
                continue;
            }
            foreach ($nodes as $node) {
                if (OMH::isUninitializedProxy($node)) {
                    continue;
                }
                $left = $meta->getReflectionProperty($tree['left'])->getValue($node);
                $right = $meta->getReflectionProperty($tree['right'])->getValue($node);
                $root = isset($tree['root']) ? $meta->getReflectionProperty($tree['root'])->getValue($node) : null;
                if ($root === $rootId && $left >= $first && $right <= $last) {
                    $oid = spl_object_hash($node);
                    $uow = $em->getUnitOfWork();

                    $meta->getReflectionProperty($tree['left'])->setValue($node, $left + $delta);
                    $uow->setOriginalEntityProperty($oid, $tree['left'], $left + $delta);
                    $meta->getReflectionProperty($tree['right'])->setValue($node, $right + $delta);
                    $uow->setOriginalEntityProperty($oid, $tree['right'], $right + $delta);
                    if (isset($tree['root'])) {
                        $meta->getReflectionProperty($tree['root'])->setValue($node, $destRootId);
                        $uow->setOriginalEntityProperty($oid, $tree['root'], $destRootId);
                    }
                    if (isset($tree['level'])) {
                        $level = $meta->getReflectionProperty($tree['level'])->getValue($node);
                        $meta->getReflectionProperty($tree['level'])->setValue($node, $level + $levelDelta);
                        $uow->setOriginalEntityProperty($oid, $tree['level'], $level + $levelDelta);
                    }
                }
            }
        }
    }
}
