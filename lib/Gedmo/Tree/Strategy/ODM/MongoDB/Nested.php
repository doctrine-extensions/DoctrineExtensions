<?php

namespace Gedmo\Tree\Strategy\ODM\MongoDB;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Proxy\Proxy;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Exception\UnexpectedValueException;
use Gedmo\Tool\Wrapper\AbstractWrapper;
use Gedmo\Tree\Strategy;
use Gedmo\Tree\TreeListener;
use Gedmo\Mapping\Event\AdapterInterface;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * This strategy makes the tree act like a nested set.
 *
 * This behavior can impact the performance of your application
 * since nested set trees are slow on inserts and updates.
 *
 * @author Litvinenko Andrey <andreylit@gmail.com>
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
     * @var TreeListener
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
            self::PREV_SIBLING,
        );
        if (!in_array($position, $valid, false)) {
            throw new InvalidArgumentException("Position: {$position} is not valid in nested set tree");
        }
        $this->nodePositions[$oid] = $position;
    }

    /**
     * {@inheritdoc}
     */
    public function processScheduledInsertion($dm, $node, AdapterInterface $ea)
    {
        /** @var ClassMetadata $meta */
        $meta = $dm->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($dm, $meta->name);

        $meta->getReflectionProperty($config['left'])->setValue($node, 0);
        $meta->getReflectionProperty($config['right'])->setValue($node, 0);

        if (isset($config['level'])) {
            $meta->getReflectionProperty($config['level'])->setValue($node, 0);
        }
        if (isset($config['root']) && !$meta->hasAssociation($config['root'])) {
            $meta->getReflectionProperty($config['root'])->setValue($node, 0);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processScheduledUpdate($dm, $node, AdapterInterface $ea)
    {
        /* @var DocumentManager $dm */
        $meta = $dm->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($dm, $meta->name);
        $uow = $dm->getUnitOfWork();

        $changeSet = $uow->getDocumentChangeSet($node);
        if (isset($config['root']) && isset($changeSet[$config['root']])) {
            throw new \Gedmo\Exception\UnexpectedValueException("Root cannot be changed manually, change parent instead");
        }

        $oid = spl_object_hash($node);
        if (isset($changeSet[$config['left']]) && isset($this->nodePositions[$oid])) {
            $wrapped = AbstractWrapper::wrap($node, $dm);
            $parent = $wrapped->getPropertyValue($config['parent']);

            // revert simulated changeset
            $uow->clearDocumentChangeSet($oid);

            $wrapped->setPropertyValue($config['left'], $changeSet[$config['left']][0]);

            $uow->setOriginalDocumentProperty($oid, $config['left'], $changeSet[$config['left']][0]);
            // set back all other changes
            foreach ($changeSet as $field => $set) {
                if ($field !== $config['left']) {
                    if (is_array($set) && array_key_exists(0, $set) && array_key_exists(1, $set)) {
                        $uow->setOriginalDocumentProperty($oid, $field, $set[0]);
                        $wrapped->setPropertyValue($field, $set[1]);
                    } else {
                        $uow->setOriginalDocumentProperty($oid, $field, $set);
                        $wrapped->setPropertyValue($field, $set);
                    }
                }
            }
            $uow->recomputeSingleDocumentChangeSet($meta, $node);
            $this->updateNode($dm, $node, $parent);
        } elseif (isset($changeSet[$config['parent']])) {
            $this->updateNode($dm, $node, $changeSet[$config['parent']][1]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processPostPersist($dm, $node, AdapterInterface $ea)
    {
        $meta = $dm->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($dm, $meta->name);

        $wrapped = AbstractWrapper::wrap($node, $dm);
        $parent = $wrapped->getPropertyValue($config['parent']);

        $this->updateNode($dm, $node, $parent, self::LAST_CHILD);
    }

    /**
     * {@inheritdoc}
     */
    public function processScheduledDelete($dm, $node)
    {
        /* @var DocumentManager $dm */

        $meta = $dm->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($dm, $meta->name);
        $uow = $dm->getUnitOfWork();

        $wrapped = AbstractWrapper::wrap($node, $dm);
        $leftValue = $wrapped->getPropertyValue($config['left']);
        $rightValue = $wrapped->getPropertyValue($config['right']);

        if (!$leftValue || !$rightValue) {
            return;
        }
        $rootId = isset($config['root']) ? $wrapped->getPropertyValue($config['root']) : null;
        $diff = $rightValue - $leftValue + 1;
        if ($diff > 2) {
            $qb = $dm
                ->createQueryBuilder($config['useObjectClass'])
                ->field($config['left'])->gt($leftValue)->lt($rightValue)
            ;

            if (isset($config['root'])) {
                $qb->field($config['root'])->equals($rootId);
            }
            $nodes = $qb->getQuery()->toArray();
            foreach ($nodes as $removalNode) {
                $uow->scheduleForDelete($removalNode);
            }
        }
        $this->shiftRL($dm, $config['useObjectClass'], $rightValue + 1, -$diff, $rootId);
    }

    /**
     * {@inheritdoc}
     */
    public function onFlushEnd($dm, AdapterInterface $ea)
    {
        // reset values
        $this->treeEdges = array();
    }

    /**
     * {@inheritdoc}
     */
    public function processPreRemove($dm, $node)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function processPrePersist($dm, $node)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function processPreUpdate($dm, $node)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function processMetadataLoad($dm, $meta)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function processPostUpdate($dm, $document, AdapterInterface $ea)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function processPostRemove($dm, $document, AdapterInterface $ea)
    {
    }

    /**
     * Update the $node with a diferent $parent
     * destination
     *
     * @param DocumentManager $dm
     * @param object        $node     - target node
     * @param object        $parent   - destination node
     * @param string        $position
     *
     * @throws \Gedmo\Exception\UnexpectedValueException
     */
    public function updateNode(DocumentManager $dm, $node, $parent, $position = 'FirstChild')
    {
        $wrapped = AbstractWrapper::wrap($node, $dm);

        /** @var ClassMetadata $meta */
        $meta = $wrapped->getMetadata();
        $config = $this->listener->getConfiguration($dm, $meta->name);

        $root = isset($config['root']) ? $wrapped->getPropertyValue($config['root']) : null;

        $identifierField = $meta->getIdentifierFieldNames()[0];

        $nodeId = $wrapped->getPropertyValue($identifierField);

        $left = $wrapped->getPropertyValue($config['left']);
        $right = $wrapped->getPropertyValue($config['right']);

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

        $newRoot = null;
        if ($parent) {
            $wrappedParent = AbstractWrapper::wrap($parent, $dm);

            $parentRoot = isset($config['root']) ? $wrappedParent->getPropertyValue($config['root']) : null;
            $parentOid = spl_object_hash($parent);
            $parentLeft = $wrappedParent->getPropertyValue($config['left']);
            $parentRight = $wrappedParent->getPropertyValue($config['right']);

            if (empty($parentLeft) && empty($parentRight)) {
                // parent node is a new node, but wasn't processed yet (due to Doctrine commit order calculator redordering)
                // We delay processing of node to the moment parent node will be processed
                if (!isset($this->delayedNodes[$parentOid])) {
                    $this->delayedNodes[$parentOid] = array();
                }
                $this->delayedNodes[$parentOid][] = array('node' => $node, 'position' => $position);

                return;
            }
            if (!$isNewNode && $root === $parentRoot && $parentLeft >= $left && $parentRight <= $right) {
                throw new UnexpectedValueException("Cannot set child as parent to node: {$nodeId}");
            }
            if (isset($config['level'])) {
                $level = $wrappedParent->getPropertyValue($config['level']);
            }

            switch ($position) {
                case self::PREV_SIBLING:
                    if (property_exists($node, 'sibling')) {
                        $wrappedSibling = AbstractWrapper::wrap($node->sibling, $dm);
                        $start = $wrappedSibling->getPropertyValue($config['left']);
                        $level++;
                    } else {
                        $newParent = $wrappedParent->getPropertyValue($config['parent']);
                        if (is_null($newParent) && (isset($config['root']) || $isNewNode)) {
                            throw new UnexpectedValueException("Cannot persist sibling for a root node, tree operation is not possible");
                        }
                        $wrapped->setPropertyValue($config['parent'], $newParent);
                        $dm->getUnitOfWork()->recomputeSingleDocumentChangeSet($meta, $node);
                        $start = $parentLeft;
                    }
                    break;

                case self::NEXT_SIBLING:
                    if (property_exists($node, 'sibling')) {
                        $wrappedSibling = AbstractWrapper::wrap($node->sibling, $dm);
                        $start = $wrappedSibling->getPropertyValue($config['right']) + 1;
                        $level++;
                    } else {
                        $newParent = $wrappedParent->getPropertyValue($config['parent']);
                        if (is_null($newParent) && (isset($config['root']) || $isNewNode)) {
                            throw new UnexpectedValueException("Cannot persist sibling for a root node, tree operation is not possible");
                        }
                        $wrapped->setPropertyValue($config['parent'], $newParent);
                        $dm->getUnitOfWork()->recomputeSingleDocumentChangeSet($meta, $node);
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
            $this->shiftRL($dm, $config['useObjectClass'], $start, $treeSize, $parentRoot);
            if (!$isNewNode && $root === $parentRoot && $left >= $start) {
                $left += $treeSize;
                $wrapped->setPropertyValue($config['left'], $left);
            }
            if (!$isNewNode && $root === $parentRoot && $right >= $start) {
                $right += $treeSize;
                $wrapped->setPropertyValue($config['right'], $right);
            }
            $newRoot = $parentRoot;
        } elseif (!isset($config['root']) ||
            ($meta->isSingleValuedAssociation($config['root']) && ($newRoot = $meta->getFieldValue($node, $config['root'])))) {

            if (!isset($this->treeEdges[$meta->name])) {
                $this->treeEdges[$meta->name] = $this->max($dm, $config['useObjectClass'], $newRoot) + 1;
            }

            $level = 0;
            $parentLeft = 0;
            $parentRight = $this->treeEdges[$meta->name];
            $this->treeEdges[$meta->name] += 2;

            switch ($position) {
                case self::PREV_SIBLING:
                    if (property_exists($node, 'sibling')) {
                        $wrappedSibling = AbstractWrapper::wrap($node->sibling, $dm);
                        $start = $wrappedSibling->getPropertyValue($config['left']);
                    } else {
                        $wrapped->setPropertyValue($config['parent'], null);
                        $dm->getUnitOfWork()->recomputeSingleDocumentChangeSet($meta, $node);
                        $start = $parentLeft + 1;
                    }
                    break;

                case self::NEXT_SIBLING:
                    if (property_exists($node, 'sibling')) {
                        $wrappedSibling = AbstractWrapper::wrap($node->sibling, $dm);
                        $start = $wrappedSibling->getPropertyValue($config['right']) + 1;
                    } else {
                        $wrapped->setPropertyValue($config['parent'], null);
                        $dm->getUnitOfWork()->recomputeSingleDocumentChangeSet($meta, $node);
                        $start = $parentRight;
                    }
                    break;

                case self::LAST_CHILD:
                    $start = $parentRight;
                    break;

                case self::FIRST_CHILD:
                default:
                    $start = $parentLeft + 1;
                    break;
            }

            $this->shiftRL($dm, $config['useObjectClass'], $start, $treeSize, null);

            if (!$isNewNode && $left >= $start) {
                $left += $treeSize;
                $wrapped->setPropertyValue($config['left'], $left);
            }
            if (!$isNewNode && $right >= $start) {
                $right += $treeSize;
                $wrapped->setPropertyValue($config['right'], $right);
            }
        } else {
            $start = 1;

            if ($meta->isSingleValuedAssociation($config['root'])) {
                $newRoot = $node;
            } else {
                $newRoot = $wrapped->getIdentifier();
            }
        }

        $diff = $start - $left;

        if (!$isNewNode) {
            $levelDiff = isset($config['level']) ? $level - $wrapped->getPropertyValue($config['level']) : null;
            $this->shiftRangeRL(
                $dm,
                $config['useObjectClass'],
                $left,
                $right,
                $diff,
                $root,
                $newRoot,
                $levelDiff
            );
            $this->shiftRL($dm, $config['useObjectClass'], $left, -$treeSize, $root);
        } else {
            $qb = $dm
                ->createQueryBuilder($config['useObjectClass'])
                ->upsert(true)
                ->update()
            ;

            // node id cannot be null
            $qb->field($identifierField)->equals($nodeId);

            if (isset($config['root'])) {
                $qb->field($config['root'])->set($newRoot);

                $wrapped->setPropertyValue($config['root'], $newRoot);
                $dm->getUnitOfWork()->setOriginalDocumentProperty($oid, $config['root'], $newRoot);
            }

            if (isset($config['level'])) {
                $qb->field($config['level'])->set($level);

                $wrapped->setPropertyValue($config['level'], $level);
                $dm->getUnitOfWork()->setOriginalDocumentProperty($oid, $config['level'], $level);
            }

            if (isset($newParent)) {
                $wrappedNewParent = AbstractWrapper::wrap($newParent, $dm);
                $newParentId = $wrappedNewParent->getIdentifier();

                $qb->field($config['parent'])->set($newParentId);

                $wrapped->setPropertyValue($config['parent'], $newParent);
                $dm->getUnitOfWork()->setOriginalDocumentProperty($oid, $config['parent'], $newParent);
            }

            $qb
                ->field($config['left'])->set($left + $diff)
                ->field($config['right'])->set($right + $diff)
            ;
            $qb->getQuery()->execute();

            $wrapped->setPropertyValue($config['left'], $left + $diff);
            $wrapped->setPropertyValue($config['right'], $right + $diff);

            $dm->getUnitOfWork()->setOriginalDocumentProperty($oid, $config['left'], $left + $diff);
            $dm->getUnitOfWork()->setOriginalDocumentProperty($oid, $config['right'], $right + $diff);
        }

        if (isset($this->delayedNodes[$oid])) {
            foreach ($this->delayedNodes[$oid] as $nodeData) {
                $this->updateNode($dm, $nodeData['node'], $node, $nodeData['position']);
            }
        }
    }

    /**
     * Get the edge of tree
     *
     * @param DocumentManager $dm
     * @param string        $class
     * @param integer       $rootId
     *
     * @return integer
     */
    public function max(DocumentManager $dm, $class, $rootId = 0)
    {
        $meta = $dm->getClassMetadata($class);
        $config = $this->listener->getConfiguration($dm, $meta->name);

        $pipeline = [];
        if (isset($config['root']) && $rootId) {
            $pipeline['$match'] = ['root' => $rootId];
        }

        $pipeline['$group'] = [
            '_id' => null,
            'right' => ['$max' => '$right']
        ];

        $right = $dm
            ->getDocumentCollection($config['useObjectClass'])
            ->aggregate([$pipeline])
            ->getSingleResult()['right']
        ;

        return intval($right);
    }

    /**
     * Shift tree left and right values by delta
     *
     * @param DocumentManager $dm
     * @param string         $class
     * @param integer        $first
     * @param integer        $delta
     * @param integer|string $root
     */
    public function shiftRL(DocumentManager $dm, $class, $first, $delta, $root = null)
    {
        $meta = $dm->getClassMetadata($class);
        $config = $this->listener->getConfiguration($dm, $class);

        $qb = $dm
            ->createQueryBuilder($config['useObjectClass'])
            ->updateMany()
            ->field($config['left'])->inc($delta)
            ->field($config['left'])->gte($first)
        ;

        if (isset($config['root'])) {
            $qb->field($config['root'])->equals($root);
        }
        $qb->getQuery()->execute();
        // ------------------------------------------------------

        $qb = $dm
            ->createQueryBuilder($config['useObjectClass'])
            ->updateMany()
            ->field($config['right'])->inc($delta)
            ->field($config['right'])->gte($first)
        ;

        if (isset($config['root'])) {
            $qb->field($config['root'])->equals($root);
        }
        $qb->getQuery()->execute();

        // update in memory nodes increases performance, saves some IO
        foreach ($dm->getUnitOfWork()->getIdentityMap() as $className => $nodes) {
            // for inheritance mapped classes, only root is always in the identity map
            if ($className !== $meta->rootDocumentName) {
                continue;
            }
            foreach ($nodes as $node) {
                if ($node instanceof Proxy && !$node->__isInitialized__) {
                    continue;
                }

                $nodeMeta = $dm->getClassMetadata(get_class($node));

                if (!array_key_exists($config['left'], $nodeMeta->getReflectionProperties())) {
                    continue;
                }

                $oid = spl_object_hash($node);
                $left = $meta->getReflectionProperty($config['left'])->getValue($node);
                $right = $meta->getReflectionProperty($config['right'])->getValue($node);
                $currentRoot = isset($config['root']) ? $meta->getReflectionProperty($config['root'])->getValue($node) : null;

                if ($currentRoot === $root && $left >= $first) {
                    $meta->getReflectionProperty($config['left'])->setValue($node, $left + $delta);
                    $dm->getUnitOfWork()->setOriginalDocumentProperty($oid, $config['left'], $left + $delta);
                }

                if ($currentRoot === $root && $right >= $first) {
                    $meta->getReflectionProperty($config['right'])->setValue($node, $right + $delta);
                    $dm->getUnitOfWork()->setOriginalDocumentProperty($oid, $config['right'], $right + $delta);
                }
            }
        }
    }

    /**
     * Shift range of right and left values on tree
     * depending on tree level difference also
     *
     * @param DocumentManager $dm
     * @param string         $class
     * @param integer        $first
     * @param integer        $last
     * @param integer        $delta
     * @param integer|string $root
     * @param integer|string $destRoot
     * @param integer        $levelDelta
     */
    public function shiftRangeRL(DocumentManager $dm, $class, $first, $last, $delta, $root = null, $destRoot = null, $levelDelta = null)
    {
        $meta = $dm->getClassMetadata($class);
        $config = $this->listener->getConfiguration($dm, $class);

        $qb = $dm
            ->createQueryBuilder($config['useObjectClass'])
            ->updateMany()
            ->field($config['left'])->inc($delta)
            ->field($config['left'])->gte($first)
            ->field($config['right'])->inc($delta)
            ->field($config['right'])->lte($last)
        ;

        if (isset($config['root'])) {
            $qb->field($config['root'])->equals($root)->set($destRoot);
        }

        if (isset($config['level'])) {
            $qb->field($config['level'])->inc($levelDelta);
        }
        $qb->getQuery()->execute();

        // update in memory nodes increases performance, saves some IO
        foreach ($dm->getUnitOfWork()->getIdentityMap() as $className => $nodes) {
            // for inheritance mapped classes, only root is always in the identity map
            if ($className !== $meta->rootDocumentName) {
                continue;
            }
            foreach ($nodes as $node) {
                if ($node instanceof Proxy && !$node->__isInitialized__) {
                    continue;
                }

                $nodeMeta = $dm->getClassMetadata(get_class($node));

                if (!array_key_exists($config['left'], $nodeMeta->getReflectionProperties())) {
                    continue;
                }

                $left = $meta->getReflectionProperty($config['left'])->getValue($node);
                $right = $meta->getReflectionProperty($config['right'])->getValue($node);

                $currentRoot = isset($config['root']) ? $meta->getReflectionProperty($config['root'])->getValue($node) : null;

                if ($currentRoot === $root && $left >= $first && $right <= $last) {
                    $oid = spl_object_hash($node);
                    $uow = $dm->getUnitOfWork();

                    $meta->getReflectionProperty($config['left'])->setValue($node, $left + $delta);
                    $uow->setOriginalDocumentProperty($oid, $config['left'], $left + $delta);

                    $meta->getReflectionProperty($config['right'])->setValue($node, $right + $delta);
                    $uow->setOriginalDocumentProperty($oid, $config['right'], $right + $delta);

                    if (isset($config['root'])) {
                        $meta->getReflectionProperty($config['root'])->setValue($node, $destRoot);
                        $uow->setOriginalDocumentProperty($oid, $config['root'], $destRoot);
                    }

                    if (isset($config['level'])) {
                        $level = $meta->getReflectionProperty($config['level'])->getValue($node);
                        $meta->getReflectionProperty($config['level'])->setValue($node, $level + $levelDelta);
                        $uow->setOriginalDocumentProperty($oid, $config['level'], $level + $levelDelta);
                    }
                }
            }
        }
    }
}
