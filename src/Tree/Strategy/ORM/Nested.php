<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tree\Strategy\ORM;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Deprecations\Deprecation;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Proxy;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Exception\UnexpectedValueException;
use Gedmo\Mapping\Event\AdapterInterface;
use Gedmo\Tool\Wrapper\AbstractWrapper;
use Gedmo\Tree\Node;
use Gedmo\Tree\Strategy;
use Gedmo\Tree\TreeListener;

/**
 * This strategy makes the tree act like a nested set.
 *
 * This behavior can impact the performance of your application
 * since nested set trees are slow on inserts and updates.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @final since gedmo/doctrine-extensions 3.11
 */
class Nested implements Strategy
{
    /**
     * Previous sibling position
     */
    public const PREV_SIBLING = 'PrevSibling';

    /**
     * Next sibling position
     */
    public const NEXT_SIBLING = 'NextSibling';

    /**
     * First child position
     */
    public const FIRST_CHILD = 'FirstChild';

    /**
     * Last child position
     */
    public const LAST_CHILD = 'LastChild';

    public const ALLOWED_NODE_POSITIONS = [
        self::PREV_SIBLING,
        self::NEXT_SIBLING,
        self::FIRST_CHILD,
        self::LAST_CHILD,
    ];

    /**
     * TreeListener
     *
     * @var TreeListener
     */
    protected $listener;

    /**
     * The max number of "right" field of the
     * tree in case few root nodes will be persisted
     * on one flush for node classes
     *
     * @var array<string, int>
     */
    private array $treeEdges = [];

    /**
     * Stores a list of node position strategies
     * for each node by object id
     *
     * @var array<int, string>
     *
     * @phpstan-var array<int, value-of<self::ALLOWED_NODE_POSITIONS>>
     */
    private array $nodePositions = [];

    /**
     * Stores a list of delayed nodes for correct order of updates
     *
     * @var array<int, array<int, array<string, Node|object|string>>>
     *
     * @phpstan-var array<int, array<int, array{node: Node|object, position: value-of<self::ALLOWED_NODE_POSITIONS>}>>
     */
    private array $delayedNodes = [];

    public function __construct(TreeListener $listener)
    {
        $this->listener = $listener;
    }

    public function getName()
    {
        return Strategy::NESTED;
    }

    /**
     * Set node position strategy
     *
     * @param int    $oid
     * @param string $position
     *
     * @return void
     */
    public function setNodePosition($oid, $position)
    {
        if (!in_array($position, self::ALLOWED_NODE_POSITIONS, true)) {
            throw new InvalidArgumentException("Position: {$position} is not valid in nested set tree");
        }
        $this->nodePositions[$oid] = $position;
    }

    public function processScheduledInsertion($em, $node, AdapterInterface $ea)
    {
        /** @var ClassMetadata<object> $meta */
        $meta = $em->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($em, $meta->getName());

        $meta->getReflectionProperty($config['left'])->setValue($node, 0);
        $meta->getReflectionProperty($config['right'])->setValue($node, 0);
        if (isset($config['level'])) {
            $meta->getReflectionProperty($config['level'])->setValue($node, 0);
        }
        if (isset($config['root']) && !$meta->hasAssociation($config['root']) && !isset($config['rootIdentifierMethod'])) {
            $meta->getReflectionProperty($config['root'])->setValue($node, 0);
        } elseif (isset($config['rootIdentifierMethod']) && null === $meta->getReflectionProperty($config['root'])->getValue($node)) {
            $meta->getReflectionProperty($config['root'])->setValue($node, 0);
        }
    }

    /**
     * @param EntityManagerInterface $em
     */
    public function processScheduledUpdate($em, $node, AdapterInterface $ea)
    {
        $meta = $em->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($em, $meta->getName());
        $uow = $em->getUnitOfWork();

        $changeSet = $uow->getEntityChangeSet($node);
        if (isset($config['root'], $changeSet[$config['root']])) {
            throw new UnexpectedValueException('Root cannot be changed manually, change parent instead');
        }

        $oid = spl_object_id($node);
        if (isset($changeSet[$config['left']], $this->nodePositions[$oid])) {
            $wrapped = AbstractWrapper::wrap($node, $em);
            $parent = $wrapped->getPropertyValue($config['parent']);
            // revert simulated changeset
            $ea->clearObjectChangeSet($uow, $node);
            $wrapped->setPropertyValue($config['left'], $changeSet[$config['left']][0]);
            $uow->setOriginalEntityProperty($oid, $config['left'], $changeSet[$config['left']][0]);
            // set back all other changes
            foreach ($changeSet as $field => $set) {
                if ($field !== $config['left']) {
                    if (is_array($set) && array_key_exists(0, $set) && array_key_exists(1, $set)) {
                        $uow->setOriginalEntityProperty($oid, $field, $set[0]);
                        $wrapped->setPropertyValue($field, $set[1]);
                    } else {
                        $uow->setOriginalEntityProperty($oid, $field, $set);
                        $wrapped->setPropertyValue($field, $set);
                    }
                }
            }
            $uow->recomputeSingleEntityChangeSet($meta, $node);
            $this->updateNode($em, $node, $parent);
        } elseif (isset($changeSet[$config['parent']])) {
            $this->updateNode($em, $node, $changeSet[$config['parent']][1]);
        }
    }

    /**
     * @param EntityManagerInterface $em
     */
    public function processPostPersist($em, $node, AdapterInterface $ea)
    {
        $meta = $em->getClassMetadata(get_class($node));

        $config = $this->listener->getConfiguration($em, $meta->getName());
        $parent = $meta->getReflectionProperty($config['parent'])->getValue($node);
        $this->updateNode($em, $node, $parent, self::LAST_CHILD);
    }

    /**
     * @param EntityManagerInterface $em
     */
    public function processScheduledDelete($em, $node)
    {
        $meta = $em->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($em, $meta->getName());
        $uow = $em->getUnitOfWork();

        $wrapped = AbstractWrapper::wrap($node, $em);
        $leftValue = $wrapped->getPropertyValue($config['left']);
        $rightValue = $wrapped->getPropertyValue($config['right']);

        if (!$leftValue || !$rightValue) {
            return;
        }
        $rootId = isset($config['root']) ? $wrapped->getPropertyValue($config['root']) : null;
        $diff = $rightValue - $leftValue + 1;
        if ($diff > 2) {
            $qb = $em->createQueryBuilder();
            $qb->select('node')
                ->from($config['useObjectClass'], 'node')
                ->where($qb->expr()->between('node.'.$config['left'], '?1', '?2'))
                ->setParameter(1, $leftValue)
                ->setParameter(2, $rightValue);

            if (isset($config['root'])) {
                $qb->andWhere($qb->expr()->eq('node.'.$config['root'], ':rid'));
                $qb->setParameter('rid', $rootId);
            }
            $q = $qb->getQuery();
            // get nodes for deletion
            foreach ($q->toIterable() as $removalNode) {
                $uow->scheduleForDelete($removalNode);
            }
        }
        $this->shiftRL($em, $config['useObjectClass'], $rightValue + 1, -$diff, $rootId);
    }

    public function onFlushEnd($em, AdapterInterface $ea)
    {
        // reset values
        $this->treeEdges = [];
    }

    public function processPreRemove($em, $node)
    {
    }

    public function processPrePersist($em, $node)
    {
    }

    public function processPreUpdate($em, $node)
    {
    }

    public function processMetadataLoad($em, $meta)
    {
    }

    public function processPostUpdate($em, $entity, AdapterInterface $ea)
    {
    }

    public function processPostRemove($em, $entity, AdapterInterface $ea)
    {
    }

    /**
     * Update the $node with a different $parent destination
     *
     * @param Node|object $node     target node
     * @param Node|object $parent   destination node
     * @param string      $position
     *
     * @phpstan-param value-of<self::ALLOWED_NODE_POSITIONS> $position
     *
     * @throws UnexpectedValueException
     *
     * @return void
     */
    public function updateNode(EntityManagerInterface $em, $node, $parent, $position = self::FIRST_CHILD)
    {
        $wrapped = AbstractWrapper::wrap($node, $em);

        /** @var ClassMetadata<object> $meta */
        $meta = $wrapped->getMetadata();
        $config = $this->listener->getConfiguration($em, $meta->getName());

        $root = isset($config['root']) ? $wrapped->getPropertyValue($config['root']) : null;
        $identifierField = $meta->getSingleIdentifierFieldName();
        $nodeId = $wrapped->getIdentifier();

        $left = $wrapped->getPropertyValue($config['left']);
        $right = $wrapped->getPropertyValue($config['right']);

        $isNewNode = empty($left) && empty($right);
        if ($isNewNode) {
            $left = 1;
            $right = 2;
        }

        $oid = spl_object_id($node);
        if (isset($this->nodePositions[$oid])) {
            $position = $this->nodePositions[$oid];
        }
        $level = $config['level_base'] ?? 0;
        $treeSize = $right - $left + 1;
        $newRoot = null;

        // @todo: In the next major release, remove all the conditions and use only the following assignment for `$sibling`.
        // $node->getSibling();

        if (method_exists($node, 'getSibling')) {
            $sibling = $node->getSibling();
        } elseif (property_exists($node, 'sibling')) {
            $sibling = $node->sibling;
        } else {
            $sibling = null;
        }

        if ($parent) {    // || (!$parent && isset($config['rootIdentifierMethod']))
            $wrappedParent = AbstractWrapper::wrap($parent, $em);

            $parentRoot = isset($config['root']) ? $wrappedParent->getPropertyValue($config['root']) : null;
            $parentOid = spl_object_id($parent);
            $parentLeft = $wrappedParent->getPropertyValue($config['left']);
            $parentRight = $wrappedParent->getPropertyValue($config['right']);
            if (empty($parentLeft) && empty($parentRight)) {
                // parent node is a new node, but wasn't processed yet (due to Doctrine commit order calculator redordering)
                // We delay processing of node to the moment parent node will be processed
                if (!isset($this->delayedNodes[$parentOid])) {
                    $this->delayedNodes[$parentOid] = [];
                }
                $this->delayedNodes[$parentOid][] = ['node' => $node, 'position' => $position];

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
                    if (null !== $sibling) {
                        $wrappedSibling = AbstractWrapper::wrap($sibling, $em);
                        $start = $wrappedSibling->getPropertyValue($config['left']);
                        ++$level;
                    } else {
                        $newParent = $wrappedParent->getPropertyValue($config['parent']);

                        if (null === $newParent && ((isset($config['root']) && $config['root'] == $config['parent']) || $isNewNode)) {
                            throw new UnexpectedValueException('Cannot persist sibling for a root node, tree operation is not possible');
                        }
                        if (null === $newParent && (isset($config['root']) || $isNewNode)) {
                            // root is a different column from parent (pointing to another table?), do nothing
                        } else {
                            $wrapped->setPropertyValue($config['parent'], $newParent);
                        }

                        $em->getUnitOfWork()->recomputeSingleEntityChangeSet($meta, $node);
                        $start = $parentLeft;
                    }

                    break;

                case self::NEXT_SIBLING:
                    if (null !== $sibling) {
                        $wrappedSibling = AbstractWrapper::wrap($sibling, $em);
                        $start = $wrappedSibling->getPropertyValue($config['right']) + 1;
                        ++$level;
                    } else {
                        $newParent = $wrappedParent->getPropertyValue($config['parent']);
                        if (null === $newParent && ((isset($config['root']) && $config['root'] == $config['parent']) || $isNewNode)) {
                            throw new UnexpectedValueException('Cannot persist sibling for a root node, tree operation is not possible');
                        }
                        if (null === $newParent && (isset($config['root']) || $isNewNode)) {
                            // root is a different column from parent (pointing to another table?), do nothing
                        } else {
                            $wrapped->setPropertyValue($config['parent'], $newParent);
                        }

                        $em->getUnitOfWork()->recomputeSingleEntityChangeSet($meta, $node);
                        $start = $parentRight + 1;
                    }

                    break;

                case self::LAST_CHILD:
                    $start = $parentRight;
                    ++$level;

                    break;

                case self::FIRST_CHILD:
                default:
                    $start = $parentLeft + 1;
                    ++$level;

                    break;
            }
            $this->shiftRL($em, $config['useObjectClass'], $start, $treeSize, $parentRoot);
            if (!$isNewNode && $root === $parentRoot && $left >= $start) {
                $left += $treeSize;
                $wrapped->setPropertyValue($config['left'], $left);
            }
            if (!$isNewNode && $root === $parentRoot && $right >= $start) {
                $right += $treeSize;
                $wrapped->setPropertyValue($config['right'], $right);
            }
            $newRoot = $parentRoot;
        } elseif (!isset($config['root'])
            || ($meta->isSingleValuedAssociation($config['root']) && null !== $parent && ($newRoot = $meta->getFieldValue($node, $config['root'])))) {
            if (!isset($this->treeEdges[$meta->getName()])) {
                $this->treeEdges[$meta->getName()] = $this->max($em, $config['useObjectClass'], $newRoot) + 1;
            }

            $level = 0;
            $parentLeft = 0;
            $parentRight = $this->treeEdges[$meta->getName()];
            $this->treeEdges[$meta->getName()] += 2;

            switch ($position) {
                case self::PREV_SIBLING:
                    if (null !== $sibling) {
                        $wrappedSibling = AbstractWrapper::wrap($sibling, $em);
                        $start = $wrappedSibling->getPropertyValue($config['left']);
                    } else {
                        $wrapped->setPropertyValue($config['parent'], null);
                        $em->getUnitOfWork()->recomputeSingleEntityChangeSet($meta, $node);
                        $start = $parentLeft + 1;
                    }

                    break;

                case self::NEXT_SIBLING:
                    if (null !== $sibling) {
                        $wrappedSibling = AbstractWrapper::wrap($sibling, $em);
                        $start = $wrappedSibling->getPropertyValue($config['right']) + 1;
                    } else {
                        $wrapped->setPropertyValue($config['parent'], null);
                        $em->getUnitOfWork()->recomputeSingleEntityChangeSet($meta, $node);
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

            $this->shiftRL($em, $config['useObjectClass'], $start, $treeSize, null);

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
            if (isset($config['rootIdentifierMethod'])) {
                $method = $config['rootIdentifierMethod'];
                $newRoot = $node->$method();
                $repo = $em->getRepository($config['useObjectClass']);

                $criteria = new Criteria();
                $criteria->andWhere(Criteria::expr()->notIn($wrapped->getMetadata()->getIdentifier()[0], [$wrapped->getIdentifier()]));
                $criteria->andWhere(Criteria::expr()->eq($config['root'], $node->$method()));
                $criteria->andWhere(Criteria::expr()->isNull($config['parent']));
                $criteria->andWhere(Criteria::expr()->eq($config['level'], 0));
                $criteria->orderBy([$config['right'] => Criteria::ASC]);
                $roots = $repo->matching($criteria)->toArray();
                $last = array_pop($roots);

                $start = ($last) ? $meta->getFieldValue($last, $config['right']) + 1 : 1;
            } elseif ($meta->isSingleValuedAssociation($config['root'])) {
                $newRoot = $node;
            } else {
                $newRoot = $wrapped->getIdentifier();
            }
        }

        $diff = $start - $left;

        if (!$isNewNode) {
            $levelDiff = isset($config['level']) ? $level - $wrapped->getPropertyValue($config['level']) : null;
            $this->shiftRangeRL(
                $em,
                $config['useObjectClass'],
                $left,
                $right,
                $diff,
                $root,
                $newRoot,
                $levelDiff
            );
            $this->shiftRL($em, $config['useObjectClass'], $left, -$treeSize, $root);
        } else {
            $qb = $em->createQueryBuilder();
            $qb->update($config['useObjectClass'], 'node');
            if (isset($config['root'])) {
                $qb->set('node.'.$config['root'], ':rid');
                $qb->setParameter('rid', $newRoot);
                $wrapped->setPropertyValue($config['root'], $newRoot);
                $em->getUnitOfWork()->setOriginalEntityProperty($oid, $config['root'], $newRoot);
            }
            if (isset($config['level'])) {
                $qb->set('node.'.$config['level'], $level);
                $wrapped->setPropertyValue($config['level'], $level);
                $em->getUnitOfWork()->setOriginalEntityProperty($oid, $config['level'], $level);
            }
            if (isset($newParent)) {
                $wrappedNewParent = AbstractWrapper::wrap($newParent, $em);
                $newParentId = $wrappedNewParent->getIdentifier();
                $qb->set('node.'.$config['parent'], ':pid');
                $qb->setParameter('pid', $newParentId);
                $wrapped->setPropertyValue($config['parent'], $newParent);
                $em->getUnitOfWork()->setOriginalEntityProperty($oid, $config['parent'], $newParent);
            }
            $qb->set('node.'.$config['left'], $left + $diff);
            $qb->set('node.'.$config['right'], $right + $diff);
            // node id cannot be null
            $qb->where($qb->expr()->eq('node.'.$identifierField, ':id'));
            $qb->setParameter('id', $nodeId);
            $qb->getQuery()->getSingleScalarResult();
            $wrapped->setPropertyValue($config['left'], $left + $diff);
            $wrapped->setPropertyValue($config['right'], $right + $diff);
            $em->getUnitOfWork()->setOriginalEntityProperty($oid, $config['left'], $left + $diff);
            $em->getUnitOfWork()->setOriginalEntityProperty($oid, $config['right'], $right + $diff);
        }
        if (isset($this->delayedNodes[$oid])) {
            foreach ($this->delayedNodes[$oid] as $nodeData) {
                $this->updateNode($em, $nodeData['node'], $node, $nodeData['position']);
            }
        }
    }

    /**
     * Get the edge of tree
     *
     * @param string $class
     * @param int    $rootId
     *
     * @phpstan-param class-string $class
     *
     * @return int
     */
    public function max(EntityManagerInterface $em, $class, $rootId = 0)
    {
        $meta = $em->getClassMetadata($class);
        $config = $this->listener->getConfiguration($em, $meta->getName());
        $qb = $em->createQueryBuilder();
        $qb->select($qb->expr()->max('node.'.$config['right']))
            ->from($config['useObjectClass'], 'node');

        if (isset($config['root']) && $rootId) {
            $qb->where($qb->expr()->eq('node.'.$config['root'], ':rid'));
            $qb->setParameter('rid', $rootId);
        }
        $query = $qb->getQuery();
        $right = $query->getSingleScalarResult();

        return (int) $right;
    }

    /**
     * Shift tree left and right values by delta
     *
     * @param string     $class
     * @param int        $first
     * @param int        $delta
     * @param int|string $root
     *
     * @phpstan-param class-string $class
     *
     * @return void
     */
    public function shiftRL(EntityManagerInterface $em, $class, $first, $delta, $root = null)
    {
        $meta = $em->getClassMetadata($class);
        $config = $this->listener->getConfiguration($em, $class);

        $sign = ($delta >= 0) ? ' + ' : ' - ';
        $absDelta = abs($delta);
        $qb = $em->createQueryBuilder();
        $qb->update($config['useObjectClass'], 'node')
            ->set('node.'.$config['left'], "node.{$config['left']} {$sign} {$absDelta}")
            ->where($qb->expr()->gte('node.'.$config['left'], $first));
        if (isset($config['root'])) {
            $qb->andWhere($qb->expr()->eq('node.'.$config['root'], ':rid'));
            $qb->setParameter('rid', $root);
        }
        $qb->getQuery()->getSingleScalarResult();

        $qb = $em->createQueryBuilder();
        $qb->update($config['useObjectClass'], 'node')
            ->set('node.'.$config['right'], "node.{$config['right']} {$sign} {$absDelta}")
            ->where($qb->expr()->gte('node.'.$config['right'], $first));
        if (isset($config['root'])) {
            $qb->andWhere($qb->expr()->eq('node.'.$config['root'], ':rid'));
            $qb->setParameter('rid', $root);
        }

        $qb->getQuery()->getSingleScalarResult();
        // update in memory nodes increases performance, saves some IO
        foreach ($em->getUnitOfWork()->getIdentityMap() as $className => $nodes) {
            // for inheritance mapped classes, only root is always in the identity map
            if ($className !== $meta->rootEntityName) {
                continue;
            }
            foreach ($nodes as $node) {
                if ($node instanceof Proxy && !$node->__isInitialized()) {
                    continue;
                }

                assert(null !== $node);

                $nodeMeta = $em->getClassMetadata(get_class($node));

                if (property_exists($nodeMeta, 'propertyAccessors')) {
                    // ORM 3.4+
                    /** @phpstan-ignore-next-line method.NotFound Method introduced in ORM 3.4 */
                    if (!array_key_exists($config['left'], $nodeMeta->getPropertyAccessors())) {
                        continue;
                    }
                } else {
                    // ORM 3.3-
                    if (!array_key_exists($config['left'], $nodeMeta->getReflectionProperties())) {
                        continue;
                    }
                }

                $oid = spl_object_id($node);
                $left = $meta->getReflectionProperty($config['left'])->getValue($node);
                $currentRoot = isset($config['root']) ? $meta->getReflectionProperty($config['root'])->getValue($node) : null;
                if ($currentRoot === $root && $left >= $first) {
                    $meta->getReflectionProperty($config['left'])->setValue($node, $left + $delta);
                    $em->getUnitOfWork()->setOriginalEntityProperty($oid, $config['left'], $left + $delta);
                }
                $right = $meta->getReflectionProperty($config['right'])->getValue($node);
                if ($currentRoot === $root && $right >= $first) {
                    $meta->getReflectionProperty($config['right'])->setValue($node, $right + $delta);
                    $em->getUnitOfWork()->setOriginalEntityProperty($oid, $config['right'], $right + $delta);
                }
            }
        }
    }

    /**
     * Shift range of right and left values on tree
     * depending on tree level difference also
     *
     * @param string     $class
     * @param int        $first
     * @param int        $last
     * @param int        $delta
     * @param int|string $root
     * @param int|string $destRoot
     * @param int        $levelDelta
     *
     * @phpstan-param class-string $class
     *
     * @return void
     */
    public function shiftRangeRL(EntityManagerInterface $em, $class, $first, $last, $delta, $root = null, $destRoot = null, $levelDelta = null)
    {
        // @todo: Remove the following condition and assignment in the next major release and use 0 as default value for
        // the `$levelDelta` parameter.
        if (null === $levelDelta && func_num_args() >= 8) {
            Deprecation::trigger(
                'gedmo/doctrine-extensions',
                'https://github.com/doctrine-extensions/DoctrineExtensions/pull/2495',
                'Passing a type different than "int" as argument 8 to "%s()" is deprecated since gedmo/doctrine-extensions'.
                ' 3.9 and will throw a "%s" error in version 4.0.',
                __METHOD__,
                \TypeError::class
            );
        }
        $levelDelta ??= 0;

        $meta = $em->getClassMetadata($class);
        $config = $this->listener->getConfiguration($em, $class);

        $sign = ($delta >= 0) ? ' + ' : ' - ';
        $absDelta = abs($delta);
        $levelSign = ($levelDelta >= 0) ? ' + ' : ' - ';
        $absLevelDelta = abs($levelDelta);

        $qb = $em->createQueryBuilder();
        $qb->update($config['useObjectClass'], 'node')
            ->set('node.'.$config['left'], "node.{$config['left']} {$sign} {$absDelta}")
            ->set('node.'.$config['right'], "node.{$config['right']} {$sign} {$absDelta}")
            ->where($qb->expr()->gte('node.'.$config['left'], $first))
            ->andWhere($qb->expr()->lte('node.'.$config['right'], $last));
        if (isset($config['root'])) {
            $qb->set('node.'.$config['root'], ':drid');
            $qb->setParameter('drid', $destRoot);
            $qb->andWhere($qb->expr()->eq('node.'.$config['root'], ':rid'));
            $qb->setParameter('rid', $root);
        }
        if (isset($config['level'])) {
            $qb->set('node.'.$config['level'], "node.{$config['level']} {$levelSign} {$absLevelDelta}");
        }
        $qb->getQuery()->getSingleScalarResult();
        // update in memory nodes increases performance, saves some IO
        foreach ($em->getUnitOfWork()->getIdentityMap() as $className => $nodes) {
            // for inheritance mapped classes, only root is always in the identity map
            if ($className !== $meta->rootEntityName) {
                continue;
            }
            foreach ($nodes as $node) {
                if ($node instanceof Proxy && !$node->__isInitialized()) {
                    continue;
                }

                assert(null !== $node);

                $nodeMeta = $em->getClassMetadata(get_class($node));

                if (property_exists($nodeMeta, 'propertyAccessors')) {
                    // ORM 3.4+
                    /** @phpstan-ignore-next-line method.NotFound Method introduced in ORM 3.4 */
                    if (!array_key_exists($config['left'], $nodeMeta->getPropertyAccessors())) {
                        continue;
                    }
                } else {
                    // ORM 3.3-
                    if (!array_key_exists($config['left'], $nodeMeta->getReflectionProperties())) {
                        continue;
                    }
                }

                $left = $meta->getReflectionProperty($config['left'])->getValue($node);
                $right = $meta->getReflectionProperty($config['right'])->getValue($node);
                $currentRoot = isset($config['root']) ? $meta->getReflectionProperty($config['root'])->getValue($node) : null;
                if ($currentRoot === $root && $left >= $first && $right <= $last) {
                    $oid = spl_object_id($node);
                    $uow = $em->getUnitOfWork();

                    $meta->getReflectionProperty($config['left'])->setValue($node, $left + $delta);
                    $uow->setOriginalEntityProperty($oid, $config['left'], $left + $delta);
                    $meta->getReflectionProperty($config['right'])->setValue($node, $right + $delta);
                    $uow->setOriginalEntityProperty($oid, $config['right'], $right + $delta);
                    if (isset($config['root'])) {
                        $meta->getReflectionProperty($config['root'])->setValue($node, $destRoot);
                        $uow->setOriginalEntityProperty($oid, $config['root'], $destRoot);
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
