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
    const PREV_SIBLING = 'prevSibling';

    /**
     * Next sibling position
     */
    const NEXT_SIBLING = 'nextSibling';

    /**
     * Last child position
     */
    const LAST_CHILD = 'lastChild';

    /**
     * First child position
     */
    const FIRST_CHILD = 'firstChild';

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
     * List of pending Nodes, which needs to
     * be post processed because of having a parent Node
     * which requires some additional calculations
     *
     * @var array
     */
    private $pendingChildNodeInserts = array();

    /**
     * List of persisted nodes for specific
     * class to know when to process pending
     * inserts
     *
     * @var array
     */
    private $persistedNodes = array();

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
     * {@inheritdoc}
     */
    public function processScheduledInsertion($em, $node)
    {
        $meta = $em->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($em, $meta->name);

        $parent = $meta->getReflectionProperty($config['parent'])->getValue($node);
        if ($parent instanceof Proxy && !$parent->__isInitialized__) {
            $em->refresh($parent);
        }
        if ($parent === null) {
            $this->prepareRoot($em, $node);
            if (isset($config['level'])) {
                $meta->getReflectionProperty($config['level'])->setValue($node, 0);
            }
        } else {
            $meta->getReflectionProperty($config['left'])->setValue($node, 0);
            $meta->getReflectionProperty($config['right'])->setValue($node, 0);
            if (isset($config['level'])) {
                $meta->getReflectionProperty($config['level'])->setValue(
                    $node,
                    $meta->getReflectionProperty($config['level'])->getValue($parent) + 1
                );
            }
            $this->pendingChildNodeInserts[$meta->name][] = $node;
        }
        $this->persistedNodes[spl_object_hash($node)] = null;
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

        if (isset($config['root'])) {
            $parent = $meta->getReflectionProperty($config['parent'])->getValue($node);
            if ($parent instanceof Proxy && !$parent->__isInitialized__) {
                $em->refresh($parent);
            }

            $identifierField = $meta->getSingleIdentifierFieldName();
            $nodeId = $meta->getReflectionProperty($identifierField)->getValue($node);
            if ($parent) {
                $rootId = $meta->getReflectionProperty($config['root'])->getValue($parent);
            } else {
                $rootId = $nodeId;
            }
            $meta->getReflectionProperty($config['root'])->setValue($node, $rootId);
            $oid = spl_object_hash($node);
            $em->getUnitOfWork()->setOriginalEntityProperty($oid, $config['root'], $rootId);
            $dql = "UPDATE {$meta->rootEntityName} node";
            $dql .= " SET node.{$config['root']} = {$rootId}";
            $dql .= " WHERE node.{$identifierField} = {$nodeId}";
            $em->createQuery($dql)->getSingleScalarResult();
        }
        unset($this->persistedNodes[spl_object_hash($node)]);

        if (!$this->persistedNodes && $this->pendingChildNodeInserts) {
            $pendingChildNodeInserts = $this->pendingChildNodeInserts;
            foreach ($pendingChildNodeInserts as $class => &$nodes) {
                while ($node = array_shift($nodes)) {
                    $this->insertChild($em, $node);
                }
            }
            $this->pendingChildNodeInserts = array();
        }
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
            $dql = "SELECT node FROM {$meta->rootEntityName} node";
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

        $this->shiftRL($em, $meta->rootEntityName, $rightValue + 1, -$diff, $rootId);
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
    {

    }

    /**
     * Insert a node which requires
     * parent synchronization
     *
     * @param EntityManager $em
     * @param object $node
     * @param string $position
     * @return void
     */
    public function insertChild(EntityManager $em, $node, $position = 'firstChild')
    {
        $meta = $em->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($em, $meta->name);

        $parent = $meta->getReflectionProperty($config['parent'])->getValue($node);

        if ($parent instanceof Proxy && !$parent->__isInitialized__) {
            $em->refresh($parent);
        }
        if (isset($config['level'])) {
            $level = $parent ? ($meta->getReflectionProperty($config['level'])->getValue($parent) + 1) : 0;
            $meta->getReflectionProperty($config['level'])->setValue($node, $level);
        }
        $identifierField = $meta->getSingleIdentifierFieldName();
        $nodeId = $meta->getReflectionProperty($identifierField)->getValue($node);

        $rootId = isset($config['root']) ? $meta->getReflectionProperty($config['root'])->getValue($parent) : null;
        $parentRight = $meta->getReflectionProperty($config['right'])->getValue($parent);
        $this->shiftRL($em, $meta->rootEntityName, $parentRight, 2, $rootId);

        $meta->getReflectionProperty($config['left'])->setValue($node, $parentRight);
        $meta->getReflectionProperty($config['right'])->setValue($node, $parentRight + 1);
        $dql = "UPDATE {$meta->rootEntityName} node";
        $dql .= " SET node.{$config['left']} = " . ($parentRight) . ', ';
        $dql .= " node.{$config['right']} = " . ($parentRight + 1);
        if (isset($level)) {
            $dql .= ", node.{$config['level']} = " . $level;
        }
        $dql .= " WHERE node.{$identifierField} = {$nodeId}";
        $em->createQuery($dql)->getSingleScalarResult();
    }

    /**
     * Update the $node with a diferent $parent
     * destination
     *
     * @todo consider $position configurable through listener
     * @param EntityManager $em
     * @param object $node - target node
     * @param object $parent - destination node
     * @param string $position
     * @throws Gedmo\Exception\UnexpectedValueException
     * @return void
     */
    public function updateNode(EntityManager $em, $node, $parent, $position = 'firstChild')
    {
        $meta = $em->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($em, $meta->name);

        $rootId = isset($config['root']) ? $meta->getReflectionProperty($config['root'])->getValue($node) : null;
        $identifierField = $meta->getSingleIdentifierFieldName();
        $nodeId = $meta->getReflectionProperty($identifierField)->getValue($node);

        $left = $meta->getReflectionProperty($config['left'])->getValue($node);
        $right = $meta->getReflectionProperty($config['right'])->getValue($node);

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
            if ($rootId === $parentRootId && $parentLeft >= $left && $parentRight <= $right) {
                throw new \Gedmo\Exception\UnexpectedValueException("Cannot set child as parent to node: {$nodeId}");
            }
            if (isset($config['level'])) {
                $level = $meta->getReflectionProperty($config['level'])->getValue($parent);
            }
            switch ($position) {
                case self::PREV_SIBLING:
                    $start = $parentLeft;
                    break;

                case self::NEXT_SIBLING:
                    $start = $parentRight + 1;
                    break;

                case self::LAST_CHILD:
                    $start = $parentRight;
                    $level++;

                case self::FIRST_CHILD:
                default:
                    $start = $parentLeft + 1;
                    $level++;
                    break;
            }
            $this->shiftRL($em, $meta->rootEntityName, $start, $treeSize, $parentRootId);
            if ($rootId === $parentRootId && $left >= $start) {
                $left += $treeSize;
                $meta->getReflectionProperty($config['left'])->setValue($node, $left);
            }
            if ($rootId === $parentRootId && $right >= $start) {
                $right += $treeSize;
                $meta->getReflectionProperty($config['right'])->setValue($node, $right);
            }
            $newRootId = $parentRootId;
        } elseif (!isset($config['root'])) {
            $start = $this->max($em, $meta->rootEntityName);
        } else {
            $start = 1;
            $newRootId = $nodeId;
        }

        $diff = $start - $left;
        /*$qb = $em->createQueryBuilder();
        $qb->update($meta->rootEntityName, 'node');
        if (isset($config['root'])) {
            //$qb->set('node.' . $config['root'], $newRootId);
        }
        if (isset($config['level'])) {
            $qb->set('node.' . $config['level'], $level);
        }
        if ($treeSize > 2 && !$isNewNode) {*/
            $levelDiff = isset($config['level']) ? $level - $meta->getReflectionProperty($config['level'])->getValue($node) : null;
            $this->shiftRangeRL(
                $em,
                $meta->rootEntityName,
                $left,
                $right,
                $diff,
                $rootId,
                $newRootId,
                $levelDiff
            );
            $this->shiftRL($em, $meta->rootEntityName, $left, -$treeSize, $rootId);
        /*} else {
            $qb->set('node.' . $config['left'], $left + $diff);
            $qb->set('node.' . $config['right'], $right + $diff);
            $qb->where("node.{$identifierField} = {$nodeId}");
            $qb->getQuery()->getSingleScalarResult();
        }*/
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

        $dql = "SELECT MAX(node.{$config['right']}) FROM {$meta->rootEntityName} node";
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

        $dql = "UPDATE {$meta->rootEntityName} node";
        $dql .= " SET node.{$config['left']} = node.{$config['left']} {$sign} {$absDelta}";
        $dql .= " WHERE node.{$config['left']} >= {$first}";
        if (isset($config['root'])) {
            $dql .= " AND node.{$config['root']} = {$rootId}";
        }
        $q = $em->createQuery($dql);
        $q->getSingleScalarResult();

        $dql = "UPDATE {$meta->rootEntityName} node";
        $dql .= " SET node.{$config['right']} = node.{$config['right']} {$sign} {$absDelta}";
        $dql .= " WHERE node.{$config['right']} >= {$first}";
        if (isset($config['root'])) {
            $dql .= " AND node.{$config['root']} = {$rootId}";
        }
        $q = $em->createQuery($dql);
        $q->getSingleScalarResult();
        // update in memory nodes increases performance, saves some IO
        foreach ($em->getUnitOfWork()->getIdentityMap() as $className => $nodes) {
            if ($className !== $meta->name && !$this->inDistriminatorMap($meta, $className)) {
                continue;
            }
            if ($className !== $meta->name) {
                $meta = $em->getClassMetadata($className);
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

        $dql = "UPDATE {$meta->rootEntityName} node";
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
            if ($className !== $meta->name && !$this->inDistriminatorMap($meta, $className)) {
                continue;
            }
            if ($className !== $meta->name) {
                $meta = $em->getClassMetadata($className);
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

    /**
     * Check if given class is in inheritance discriminator map
     *
     * @param ClassMetadataInfo $meta
     * @param string $className
     * @return boolean
     */
    public function inDistriminatorMap(ClassMetadataInfo $meta, $className)
    {
        foreach ($meta->discriminatorMap as $class) {
            if ($className === $class) {
                return true;
            }
        }
        return false;
    }

    /**
     * If Node does not have parent, set it as root
     *
     * @param EntityManager $em
     * @param object $entity
     * @return void
     */
    private function prepareRoot(EntityManager $em, $node)
    {
        $meta = $em->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($em, $meta->name);

        if (isset($config['root'])) {
            $meta->getReflectionProperty($config['root'])->setValue($node, null);
            $meta->getReflectionProperty($config['left'])->setValue($node, 1);
            $meta->getReflectionProperty($config['right'])->setValue($node, 2);
        } else {
            $edge = isset($this->treeEdges[$meta->name]) ?
            $this->treeEdges[$meta->name] : $this->max($em, $meta->rootEntityName);
            $meta->getReflectionProperty($config['left'])->setValue($node, $edge + 1);
            $meta->getReflectionProperty($config['right'])->setValue($node, $edge + 2);
            $this->treeEdges[$meta->name] = $edge + 2;
        }
    }
}
