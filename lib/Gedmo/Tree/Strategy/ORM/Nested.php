<?php

namespace Gedmo\Tree\Strategy\ORM;

use Doctrine\ORM\Proxy\Proxy;
use Gedmo\Tree\Strategy,
    Doctrine\ORM\EntityManager,
    Gedmo\Tree\TreeListener,
    Doctrine\ORM\Mapping\ClassMetadataInfo,
    Doctrine\ORM\Query;

/**
 * This strategy makes tree act like
 * nested set.
 *
 * This behavior can inpact the performance of your application
 * since nested set trees are slow on inserts and updates.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree.Strategy.ORM
 * @subpackage Nested
 * @link http://www.gediminasm.org
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
    public function processScheduledInsertion($em, $node)
    {
        $meta = $em->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($em, $meta->name);

        $meta->getReflectionProperty($config['left'])->setValue($node, 0);
        $meta->getReflectionProperty($config['right'])->setValue($node, 0);
        if (isset($config['level'])) {
            $meta->getReflectionProperty($config['level'])->setValue($node, 0);
        }
        if (isset($config['root'])) {
            $meta->getReflectionProperty($config['root'])->setValue($node, 0);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processScheduledUpdate($em, $node)
    {
        $meta = $em->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($em, $meta->name);
        $uow = $em->getUnitOfWork();

        $changeSet = $uow->getEntityChangeSet($node);
        if (isset($config['root']) && isset($changeSet[$config['root']])) {
            throw new \Gedmo\Exception\UnexpectedValueException("Root cannot be changed manualy, change parent instead");
        }
        if (isset($changeSet[$config['parent']])) {
            $this->updateNode($em, $node, $changeSet[$config['parent']][1]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processPostPersist($em, $node)
    {
        $meta = $em->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($em, $meta->name);
        $parent = $meta->getReflectionProperty($config['parent'])->getValue($node);
        $this->updateNode($em, $node, $parent, self::LAST_CHILD);
    }

    /**
     * {@inheritdoc}
     */
    public function processScheduledDelete($em, $node)
    {
        $meta = $em->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($em, $meta->name);
        $uow = $em->getUnitOfWork();

        $leftValue = $meta->getReflectionProperty($config['left'])->getValue($node);
        $rightValue = $meta->getReflectionProperty($config['right'])->getValue($node);

        if (!$leftValue || !$rightValue) {
            return;
        }
        $rootId = isset($config['root']) ? $meta->getReflectionProperty($config['root'])->getValue($node) : null;
        $diff = $rightValue - $leftValue + 1;
        if ($diff > 2) {
            $dql = "SELECT node FROM {$config['useObjectClass']} node";
            $dql .= " WHERE node.{$config['left']} BETWEEN :left AND :right";
            if (isset($config['root'])) {
                $dql .= " AND node.{$config['root']} = {$rootId}";
            }
            $q = $em->createQuery($dql);
            // get nodes for deletion
            $q->setParameter('left', $leftValue + 1);
            $q->setParameter('right', $rightValue - 1);
            $nodes = $q->getResult();
            foreach ((array)$nodes as $removalNode) {
                $uow->scheduleForDelete($removalNode);
            }
        }

        $this->shiftRL($em, $config['useObjectClass'], $rightValue + 1, -$diff, $rootId);
    }

    /**
     * {@inheritdoc}
     */
    public function onFlushEnd($em)
    {
        // reset values
        $this->treeEdges = array();
        $this->updatesOnNodeClasses = array();
    }

    /**
     * {@inheritdoc}
     */
    public function processPreRemove($em, $node)
    {}

    /**
     * {@inheritdoc}
     */
    public function processPrePersist($em, $node)
    {}

    /**
     * {@inheritdoc}
     */
    public function processMetadataLoad($em, $meta)
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
        $meta = $em->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($em, $meta->name);

        $rootId = isset($config['root']) ? $meta->getReflectionProperty($config['root'])->getValue($node) : null;
        $identifierField = $meta->getSingleIdentifierFieldName();
        $nodeId = $meta->getReflectionProperty($identifierField)->getValue($node);

        $left = $meta->getReflectionProperty($config['left'])->getValue($node);
        $right = $meta->getReflectionProperty($config['right'])->getValue($node);

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
            if ($parent instanceof Proxy && !$parent->__isInitialized__) {
                $em->refresh($parent);
            }

            $parentRootId = isset($config['root']) ? $meta->getReflectionProperty($config['root'])->getValue($parent) : null;
            $parentLeft = $meta->getReflectionProperty($config['left'])->getValue($parent);
            $parentRight = $meta->getReflectionProperty($config['right'])->getValue($parent);
            if (!$isNewNode && $rootId === $parentRootId && $parentLeft >= $left && $parentRight <= $right) {
                throw new \Gedmo\Exception\UnexpectedValueException("Cannot set child as parent to node: {$nodeId}");
            }
            if (isset($config['level'])) {
                $level = $meta->getReflectionProperty($config['level'])->getValue($parent);
            }
            switch ($position) {
                case self::PREV_SIBLING:
                    $newParent = $meta->getReflectionProperty($config['parent'])->getValue($parent);
                    if (!$isNewNode) {
                        $meta->getReflectionProperty($config['parent'])->setValue($node, $newParent);
                        $em->getUnitOfWork()->recomputeSingleEntityChangeSet($meta, $node);
                    }
                    $start = $parentLeft;
                    break;

                case self::NEXT_SIBLING:
                    $newParent = $meta->getReflectionProperty($config['parent'])->getValue($parent);
                    if (!$isNewNode) {
                        $meta->getReflectionProperty($config['parent'])->setValue($node, $newParent);
                        $em->getUnitOfWork()->recomputeSingleEntityChangeSet($meta, $node);
                    }
                    $start = $parentRight + 1;
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
            $this->shiftRL($em, $config['useObjectClass'], $start, $treeSize, $parentRootId);
            if (!$isNewNode && $rootId === $parentRootId && $left >= $start) {
                $left += $treeSize;
                $meta->getReflectionProperty($config['left'])->setValue($node, $left);
            }
            if (!$isNewNode && $rootId === $parentRootId && $right >= $start) {
                $right += $treeSize;
                $meta->getReflectionProperty($config['right'])->setValue($node, $right);
            }
            $newRootId = $parentRootId;
        } elseif (!isset($config['root'])) {
            $start = isset($this->treeEdges[$meta->name]) ?
                $this->treeEdges[$meta->name] : $this->max($em, $config['useObjectClass']);
            $this->treeEdges[$meta->name] = $start + 2;
            $start++;
        } else {
            $start = 1;
            $newRootId = $nodeId;
        }

        $diff = $start - $left;
        if (!$isNewNode) {
            $levelDiff = isset($config['level']) ? $level - $meta->getReflectionProperty($config['level'])->getValue($node) : null;
            $this->shiftRangeRL(
                $em,
                $config['useObjectClass'],
                $left,
                $right,
                $diff,
                $rootId,
                $newRootId,
                $levelDiff
            );
            $this->shiftRL($em, $config['useObjectClass'], $left, -$treeSize, $rootId);
        } else {
            $qb = $em->createQueryBuilder();
            $qb->update($config['useObjectClass'], 'node');
            if (isset($config['root'])) {
                $qb->set('node.' . $config['root'], $newRootId);
                $meta->getReflectionProperty($config['root'])->setValue($node, $newRootId);
                $em->getUnitOfWork()->setOriginalEntityProperty($oid, $config['root'], $newRootId);
            }
            if (isset($config['level'])) {
                $qb->set('node.' . $config['level'], $level);
                $meta->getReflectionProperty($config['level'])->setValue($node, $level);
                $em->getUnitOfWork()->setOriginalEntityProperty($oid, $config['level'], $level);
            }
            if (isset($newParent)) {
                if ($newParent instanceof Proxy && !$newParent->__isInitialized__) {
                    $em->refresh($newParent);
                }
                $newParentId = $meta->getReflectionProperty($identifierField)->getValue($newParent);
                $qb->set('node.' . $config['parent'], $newParentId);
                $meta->getReflectionProperty($config['parent'])->setValue($node, $newParent);
                $em->getUnitOfWork()->setOriginalEntityProperty($oid, $config['parent'], $newParent);
            }
            $qb->set('node.' . $config['left'], $left + $diff);
            $qb->set('node.' . $config['right'], $right + $diff);
            $qb->where("node.{$identifierField} = {$nodeId}");
            $qb->getQuery()->getSingleScalarResult();
            $meta->getReflectionProperty($config['left'])->setValue($node, $left + $diff);
            $em->getUnitOfWork()->setOriginalEntityProperty($oid, $config['left'], $left + $diff);
            $meta->getReflectionProperty($config['right'])->setValue($node, $right + $diff);
            $em->getUnitOfWork()->setOriginalEntityProperty($oid, $config['right'], $right + $diff);
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
        $config = $this->listener->getConfiguration($em, $meta->name);

        $dql = "SELECT MAX(node.{$config['right']}) FROM {$config['useObjectClass']} node";
        if (isset($config['root']) && $rootId) {
            $dql .= " WHERE node.{$config['root']} = {$rootId}";
        }

        $query = $em->createQuery($dql);
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
     * @param integer $rootId
     * @return void
     */
    public function shiftRL(EntityManager $em, $class, $first, $delta, $rootId = null)
    {
        $meta = $em->getClassMetadata($class);
        $config = $this->listener->getConfiguration($em, $class);

        $sign = ($delta >= 0) ? ' + ' : ' - ';
        $absDelta = abs($delta);

        $dql = "UPDATE {$meta->name} node";
        $dql .= " SET node.{$config['left']} = node.{$config['left']} {$sign} {$absDelta}";
        $dql .= " WHERE node.{$config['left']} >= {$first}";
        if (isset($config['root'])) {
            $dql .= " AND node.{$config['root']} = {$rootId}";
        }
        $q = $em->createQuery($dql);
        $q->getSingleScalarResult();

        $dql = "UPDATE {$meta->name} node";
        $dql .= " SET node.{$config['right']} = node.{$config['right']} {$sign} {$absDelta}";
        $dql .= " WHERE node.{$config['right']} >= {$first}";
        if (isset($config['root'])) {
            $dql .= " AND node.{$config['root']} = {$rootId}";
        }
        $q = $em->createQuery($dql);
        $q->getSingleScalarResult();
        // update in memory nodes increases performance, saves some IO
        foreach ($em->getUnitOfWork()->getIdentityMap() as $className => $nodes) {
            // for inheritance mapped classes, only root is always in the identity map
            if ($className !== $meta->rootEntityName) {
                continue;
            }
            foreach ($nodes as $node) {
                if ($node instanceof Proxy && !$node->__isInitialized__) {
                    continue;
                }
                $oid = spl_object_hash($node);
                $left = $meta->getReflectionProperty($config['left'])->getValue($node);
                $root = isset($config['root']) ? $meta->getReflectionProperty($config['root'])->getValue($node) : null;
                if ($root === $rootId && $left >= $first) {
                    $meta->getReflectionProperty($config['left'])->setValue($node, $left + $delta);
                    $em->getUnitOfWork()->setOriginalEntityProperty($oid, $config['left'], $left + $delta);
                }
                $right = $meta->getReflectionProperty($config['right'])->getValue($node);
                if ($root === $rootId && $right >= $first) {
                    $meta->getReflectionProperty($config['right'])->setValue($node, $right + $delta);
                    $em->getUnitOfWork()->setOriginalEntityProperty($oid, $config['right'], $right + $delta);
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
     * @param integer $rootId
     * @param integer $destRootId
     * @param integer $levelDelta
     * @return void
     */
    public function shiftRangeRL(EntityManager $em, $class, $first, $last, $delta, $rootId = null, $destRootId = null, $levelDelta = null)
    {
        $meta = $em->getClassMetadata($class);
        $config = $this->listener->getConfiguration($em, $class);

        $sign = ($delta >= 0) ? ' + ' : ' - ';
        $absDelta = abs($delta);
        $levelSign = ($levelDelta >= 0) ? ' + ' : ' - ';
        $absLevelDelta = abs($levelDelta);

        $dql = "UPDATE {$meta->name} node";
        $dql .= " SET node.{$config['left']} = node.{$config['left']} {$sign} {$absDelta}";
        $dql .= ", node.{$config['right']} = node.{$config['right']} {$sign} {$absDelta}";
        if (isset($config['root'])) {
            $dql .= ", node.{$config['root']} = {$destRootId}";
        }
        if (isset($config['level'])) {
            $dql .= ", node.{$config['level']} = node.{$config['level']} {$levelSign} {$absLevelDelta}";
        }
        $dql .= " WHERE node.{$config['left']} >= {$first}";
        $dql .= " AND node.{$config['right']} <= {$last}";
        if (isset($config['root'])) {
            $dql .= " AND node.{$config['root']} = {$rootId}";
        }
        $q = $em->createQuery($dql);
        $q->getSingleScalarResult();
        // update in memory nodes increases performance, saves some IO
        foreach ($em->getUnitOfWork()->getIdentityMap() as $className => $nodes) {
            // for inheritance mapped classes, only root is always in the identity map
            if ($className !== $meta->rootEntityName) {
                continue;
            }
            foreach ($nodes as $node) {
                if ($node instanceof Proxy && !$node->__isInitialized__) {
                    continue;
                }
                $left = $meta->getReflectionProperty($config['left'])->getValue($node);
                $right = $meta->getReflectionProperty($config['right'])->getValue($node);
                $root = isset($config['root']) ? $meta->getReflectionProperty($config['root'])->getValue($node) : null;
                if ($root === $rootId && $left >= $first && $right <= $last) {
                    $oid = spl_object_hash($node);
                    $uow = $em->getUnitOfWork();

                    $meta->getReflectionProperty($config['left'])->setValue($node, $left + $delta);
                    $uow->setOriginalEntityProperty($oid, $config['left'], $left + $delta);
                    $meta->getReflectionProperty($config['right'])->setValue($node, $right + $delta);
                    $uow->setOriginalEntityProperty($oid, $config['right'], $right + $delta);
                    if (isset($config['root'])) {
                        $meta->getReflectionProperty($config['root'])->setValue($node, $destRootId);
                        $uow->setOriginalEntityProperty($oid, $config['root'], $destRootId);
                    }
                    if (isset($config['level'])) {
                        $level = $meta->getReflectionProperty($config['level'])->getValue($node);
                        $meta->getReflectionProperty($config['level'])->setValue($node, $level + $levelDelta);
                        $uow->setOriginalEntityProperty($oid, $config['level'], $level + $levelDelta);
                    }
                }
            }
        }
    }
}
