<?php

namespace Gedmo\Tree\Entity\Repository;

use Doctrine\ORM\Query;
use Gedmo\Tree\Strategy;
use Gedmo\Tree\Strategy\ORM\Nested;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Exception\UnexpectedValueException;

/**
 * The NestedTreeRepository has some useful functions
 * to interact with NestedSet tree. Repository uses
 * the strategy used by listener
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class NestedTreeRepository extends AbstractTreeRepository
{
    /**
     * {@inheritDoc}
     */
    public function getRootNodesQueryBuilder($sortByField = null, $direction = 'asc')
    {
        $meta = $this->getClassMetadata();
        $tree = $this->listener->getConfiguration($this->_em, $meta->name)->getMapping();
        $qb = $this->_em->createQueryBuilder();
        $qb
            ->select('node')
            ->from($tree['rootClass'], 'node')
            ->where($qb->expr()->isNull('node.'.$tree['parent']))
        ;

        if ($sortByField !== null) {
            $qb->orderBy('node.' . $sortByField, strtolower($direction) === 'asc' ? 'asc' : 'desc');
        } else {
            $qb->orderBy('node.' . $tree['left'], 'ASC');
        }

        return $qb;
    }

    /**
     * {@inheritDoc}
     */
    public function getRootNodesQuery($sortByField = null, $direction = 'asc')
    {
        return $this->getRootNodesQueryBuilder($sortByField, $direction)->getQuery();
    }

    /**
     * {@inheritDoc}
     */
    public function getRootNodes($sortByField = null, $direction = 'asc')
    {
        return $this->getRootNodesQuery($sortByField, $direction)->getResult();
    }

    /**
     * Allows the following 'virtual' methods:
     * - persistAsFirstChild($node)
     * - persistAsFirstChildOf($node, $parent)
     * - persistAsLastChild($node)
     * - persistAsLastChildOf($node, $parent)
     * - persistAsNextSibling($node)
     * - persistAsNextSiblingOf($node, $sibling)
     * - persistAsPrevSibling($node)
     * - persistAsPrevSiblingOf($node, $sibling)
     * Inherited virtual methods:
     * - find*
     *
     * @see \Doctrine\ORM\EntityRepository
     * @throws InvalidArgumentException - If arguments are invalid
     * @throws BadMethodCallException - If the method called is an invalid find* or persistAs* method
     *      or no find* either persistAs* method at all and therefore an invalid method call.
     * @return mixed - TreeNestedRepository if persistAs* is called
     */
    public function __call($method, $args)
    {
        if (substr($method, 0, 9) === 'persistAs') {
            if (!isset($args[0])) {
                throw new \Gedmo\Exception\InvalidArgumentException('Node to persist must be available as first argument');
            }
            $node = $args[0];
            $meta = $this->getClassMetadata();
            $tree = $this->listener->getConfiguration($this->_em, $meta->name)->getMapping();
            $position = substr($method, 9);
            if (substr($method, -2) === 'Of') {
                if (!isset($args[1])) {
                    throw new InvalidArgumentException('If "Of" is specified you must provide parent or sibling as the second argument');
                }
                $parentOrSibling = $args[1];
                if (strstr($method,'Sibling')) {
                    $this->_em->initializeObject($parentOrSibling);
                    $newParent = $meta->getReflectionProperty($tree['parent'])->getValue($parentOrSibling);
                    if (null === $newParent && isset($config['root'])) {
                        throw new UnexpectedValueException("Cannot persist sibling for a root node, tree operation is not possible");
                    }
                    $node->sibling = $parentOrSibling;
                    $parentOrSibling = $newParent;
                }
                $meta->getReflectionProperty($tree['parent'])->setValue($node, $parentOrSibling);
                $position = substr($position, 0, -2);
            }
            $meta->getReflectionProperty($tree['left'])->setValue($node, 0); // simulate changeset
            $oid = spl_object_hash($node);
            $this->listener
                ->getStrategy($this->_em, $meta->name)
                ->setNodePosition($oid, $position)
            ;

            $this->_em->persist($node);
            return $this;
        }
        return parent::__call($method, $args);
    }

    /**
     * Get the Tree path query builder by given $node
     *
     * @param object $node
     * @throws InvalidArgumentException - if input is not valid
     * @return Doctrine\ORM\QueryBuilder
     */
    public function getPathQueryBuilder($node)
    {
        $meta = $this->getClassMetadata();
        if (!$node instanceof $meta->name) {
            throw new InvalidArgumentException("Node is not related to this repository");
        }
        $tree = $this->listener->getConfiguration($this->_em, $meta->name)->getMapping();
        if (!$this->_em->getUnitOfWork()->isInIdentityMap($node)) {
            throw new InvalidArgumentException("Node is not managed by UnitOfWork");
        }
        $this->_em->initializeObject($node);
        $left = $meta->getReflectionProperty($tree['left'])->getValue($node);
        $right = $meta->getReflectionProperty($tree['right'])->getValue($node);
        $qb = $this->_em->createQueryBuilder();
        $qb->select('node')
            ->from($tree['rootClass'], 'node')
            ->where($qb->expr()->lte('node.'.$tree['left'], $left))
            ->andWhere($qb->expr()->gte('node.'.$tree['right'], $right))
            ->orderBy('node.' . $tree['left'], 'ASC')
        ;
        if (isset($tree['root'])) {
            $rootId = $meta->getReflectionProperty($tree['root'])->getValue($node);
            $qb->andWhere($rootId === null ?
                $qb->expr()->isNull('node.'.$tree['root']) :
                $qb->expr()->eq('node.'.$tree['root'], is_string($rootId) ? $qb->expr()->literal($rootId) : $rootId)
            );
        }
        return $qb;
    }

    /**
     * Get the Tree path query by given $node
     *
     * @param object $node
     * @return Doctrine\ORM\Query
     */
    public function getPathQuery($node)
    {
        return $this->getPathQueryBuilder($node)->getQuery();
    }

    /**
     * Get the Tree path of Nodes by given $node
     *
     * @param object $node
     * @return array - list of Nodes in path
     */
    public function getPath($node)
    {
        return $this->getPathQuery($node)->getResult();
    }

    /**
     * @see getChildrenQueryBuilder
     */
    public function childrenQueryBuilder($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        $meta = $this->getClassMetadata();
        $tree = $this->listener->getConfiguration($this->_em, $meta->name)->getMapping();
        $qb = $this->_em->createQueryBuilder();
        $qb->select('node')
            ->from($tree['rootClass'], 'node')
        ;
        if ($node !== null) {
            if ($node instanceof $meta->name) {
                if (!$this->_em->getUnitOfWork()->isInIdentityMap($node)) {
                    throw new InvalidArgumentException("Node is not managed by UnitOfWork");
                }
                $this->_em->initializeObject($node);
                if ($direct) {
                    $id = $meta->getReflectionProperty($meta->getSingleIdentifierFieldName())->getValue($node);
                    $qb->where($id === null ?
                        $qb->expr()->isNull('node.'.$tree['parent']) :
                        $qb->expr()->eq('node.'.$tree['parent'], is_string($id) ? $qb->expr()->literal($id) : $id)
                    );
                } else {
                    $left = $meta->getReflectionProperty($tree['left'])->getValue($node);
                    $right = $meta->getReflectionProperty($tree['right'])->getValue($node);
                    if ($left && $right) {
                        $qb
                            ->where($qb->expr()->lt('node.' . $tree['right'], $right))
                            ->andWhere($qb->expr()->gt('node.' . $tree['left'], $left))
                        ;
                    }
                }
                if (isset($tree['root'])) {
                    $rootId = $meta->getReflectionProperty($tree['root'])->getValue($node);
                    $qb->andWhere($rootId === null ?
                        $qb->expr()->isNull('node.'.$tree['root']) :
                        $qb->expr()->eq('node.'.$tree['root'], is_string($rootId) ? $qb->expr()->literal($rootId) : $rootId)
                    );
                }
                if ($includeNode) {
                    $idField = $meta->getSingleIdentifierFieldName();
                    $qb->where('('.$qb->getDqlPart('where').') OR node.'.$idField.' = :rootNode');
                    $qb->setParameter('rootNode', $node);
                }
            } else {
                throw new \InvalidArgumentException("Node is not related to this repository");
            }
        } else {
            if ($direct) {
                $qb->where($qb->expr()->isNull('node.' . $tree['parent']));
            }
        }
        if (!$sortByField) {
            $qb->orderBy('node.' . $tree['left'], 'ASC');
        } elseif (is_array($sortByField)) {
            $fields = '';
            foreach ($sortByField as $field) {
                $fields .= 'node.'.$field.',';
            }
            $fields = rtrim($fields, ',');
            $qb->orderBy($fields, $direction);
        } else {
            if ($meta->hasField($sortByField) && in_array(strtolower($direction), array('asc', 'desc'))) {
                $qb->orderBy('node.' . $sortByField, $direction);
            } else {
                throw new InvalidArgumentException("Invalid sort options specified: field - {$sortByField}, direction - {$direction}");
            }
        }
        return $qb;
    }

    /**
     * @see getChildrenQuery
     */
    public function childrenQuery($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        return $this->childrenQueryBuilder($node, $direct, $sortByField, $direction, $includeNode)->getQuery();
    }

    /**
     * @see getChildren
     */
    public function children($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        $q = $this->childrenQuery($node, $direct, $sortByField, $direction, $includeNode);
        return $q->getResult();
    }

    /**
     * {@inheritDoc}
     */
    public function getChildrenQueryBuilder($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        return $this->childrenQueryBuilder($node, $direct, $sortByField, $direction, $includeNode);
    }

    /**
     * {@inheritDoc}
     */
    public function getChildrenQuery($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        return $this->childrenQuery($node, $direct, $sortByField, $direction, $includeNode);
    }

    /**
     * {@inheritDoc}
     */
    public function getChildren($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        return $this->children($node, $direct, $sortByField, $direction, $includeNode);
    }

    /**
     * Get tree leafs query builder
     *
     * @param object $root - root node in case of root tree is required
     * @param string $sortByField - field name to sort by
     * @param string $direction - sort direction : "ASC" or "DESC"
     * @throws InvalidArgumentException - if input is not valid
     * @return Doctrine\ORM\QueryBuilder
     */
    public function getLeafsQueryBuilder($root = null, $sortByField = null, $direction = 'ASC')
    {
        $meta = $this->getClassMetadata();
        $tree = $this->listener->getConfiguration($this->_em, $meta->name)->getMapping();

        if (isset($tree['root']) && null === $root) {
            if (null === $root) {
                throw new InvalidArgumentException("If tree has root, getLeafs method requires any node of this tree");
            }
        }

        $qb = $this->_em->createQueryBuilder();
        $qb->select('node')
            ->from($tree['rootClass'], 'node')
            ->where($qb->expr()->eq('node.' . $tree['right'], '1 + node.' . $tree['left']))
        ;
        if (isset($tree['root'])) {
            if ($root instanceof $meta->name) {
                if (!$this->_em->getUnitOfWork()->isInIdentityMap($root)) {
                    throw new InvalidArgumentException("Root is not managed by UnitOfWork");
                }
                $this->_em->initializeObject($root);
                $rootId = $meta->getReflectionProperty($tree['root'])->getValue($root);
                if (!$rootId) {
                    throw new InvalidArgumentException("Root node must be managed");
                }
                $qb->andWhere($qb->expr()->eq(
                    'node.'.$tree['root'],
                    is_string($rootId) ? $qb->expr()->literal($rootId) : $rootId
                ));
            } else {
                throw new InvalidArgumentException("Node is not related to this repository");
            }
        }
        if (!$sortByField) {
            if (isset($tree['root'])) {
                $qb->addOrderBy('node.' . $tree['root'], 'ASC');
            }
            $qb->addOrderBy('node.' . $tree['left'], 'ASC', true);
        } else {
            if ($meta->hasField($sortByField) && in_array(strtolower($direction), array('asc', 'desc'))) {
                $qb->orderBy('node.' . $sortByField, $direction);
            } else {
                throw new InvalidArgumentException("Invalid sort options specified: field - {$sortByField}, direction - {$direction}");
            }
        }
        return $qb;
    }

    /**
     * Get tree leafs query
     *
     * @param object $root - root node in case of root tree is required
     * @param string $sortByField - field name to sort by
     * @param string $direction - sort direction : "ASC" or "DESC"
     * @return Doctrine\ORM\Query
     */
    public function getLeafsQuery($root = null, $sortByField = null, $direction = 'ASC')
    {
        return $this->getLeafsQueryBuilder($root, $sortByField, $direction)->getQuery();
    }

    /**
     * Get list of leaf nodes of the tree
     *
     * @param object $root - root node in case of root tree is required
     * @param string $sortByField - field name to sort by
     * @param string $direction - sort direction : "ASC" or "DESC"
     * @return array
     */
    public function getLeafs($root = null, $sortByField = null, $direction = 'ASC')
    {
        return $this->getLeafsQuery($root, $sortByField, $direction)->getResult();
    }

    /**
     * Get the query builder for next siblings of the given $node
     *
     * @param object $node
     * @param bool $includeSelf - include the node itself
     * @throws \Gedmo\Exception\InvalidArgumentException - if input is invalid
     * @return Doctrine\ORM\QueryBuilder
     */
    public function getNextSiblingsQueryBuilder($node, $includeSelf = false)
    {
        $meta = $this->getClassMetadata();
        if (!$node instanceof $meta->name) {
            throw new InvalidArgumentException("Node is not related to this repository");
        }
        if (!$this->_em->getUnitOfWork()->isInIdentityMap($node)) {
            throw new InvalidArgumentException("Node is not managed by UnitOfWork");
        }

        $this->_em->initializeObject($node);
        $tree = $this->listener->getConfiguration($this->_em, $meta->name)->getMapping();
        $parent = $meta->getReflectionProperty($tree['parent'])->getValue($node);
        if (isset($tree['root']) && !$parent) {
            throw new InvalidArgumentException("Cannot get siblings from tree root node");
        }

        $left = $meta->getReflectionProperty($tree['left'])->getValue($node);

        $qb = $this->_em->createQueryBuilder();
        $qb->select('node')
            ->from($tree['rootClass'], 'node')
            ->where($includeSelf ?
                $qb->expr()->gte('node.'.$tree['left'], $left) :
                $qb->expr()->gt('node.'.$tree['left'], $left)
            )
            ->orderBy("node.{$tree['left']}", 'ASC')
        ;
        if ($parent) {
            $this->_em->initializeObject($parent);
            $id = $meta->getReflectionProperty($meta->getSingleIdentifierFieldName())->getValue($parent);
            $qb->andWhere($qb->expr()->eq('node.'.$tree['parent'], is_string($id) ? $qb->expr()->literal($id) : $id));
        } else {
            $qb->andWhere($qb->expr()->isNull('node.'.$tree['parent']));
        }
        return $qb;
    }

    /**
     * Get the query for next siblings of the given $node
     *
     * @param object $node
     * @param bool $includeSelf - include the node itself
     * @return Doctrine\ORM\Query
     */
    public function getNextSiblingsQuery($node, $includeSelf = false)
    {
        return $this->getNextSiblingsQueryBuilder($node, $includeSelf)->getQuery();
    }

    /**
     * Find the next siblings of the given $node
     *
     * @param object $node
     * @param bool $includeSelf - include the node itself
     * @return array
     */
    public function getNextSiblings($node, $includeSelf = false)
    {
        return $this->getNextSiblingsQuery($node, $includeSelf)->getResult();
    }

    /**
     * Get query builder for previous siblings of the given $node
     *
     * @param object $node
     * @param bool $includeSelf - include the node itself
     * @throws \Gedmo\Exception\InvalidArgumentException - if input is invalid
     * @return Doctrine\ORM\QueryBuilder
     */
    public function getPrevSiblingsQueryBuilder($node, $includeSelf = false)
    {
        $meta = $this->getClassMetadata();
        if (!$node instanceof $meta->name) {
            throw new InvalidArgumentException("Node is not related to this repository");
        }
        if (!$this->_em->getUnitOfWork()->isInIdentityMap($node)) {
            throw new InvalidArgumentException("Node is not managed by UnitOfWork");
        }

        $this->_em->initializeObject($node);
        $tree = $this->listener->getConfiguration($this->_em, $meta->name)->getMapping();
        $parent = $meta->getReflectionProperty($tree['parent'])->getValue($node);
        if (isset($tree['root']) && !$parent) {
            throw new InvalidArgumentException("Cannot get siblings from tree root node");
        }

        $left = $meta->getReflectionProperty($tree['left'])->getValue($node);

        $qb = $this->_em->createQueryBuilder();
        $qb->select('node')
            ->from($tree['rootClass'], 'node')
            ->where($includeSelf ?
                $qb->expr()->lte('node.'.$tree['left'], $left) :
                $qb->expr()->lt('node.'.$tree['left'], $left)
            )
            ->orderBy("node.{$tree['left']}", 'ASC')
        ;
        if ($parent) {
            $this->_em->initializeObject($parent);
            $id = $meta->getReflectionProperty($meta->getSingleIdentifierFieldName())->getValue($parent);
            $qb->andWhere($qb->expr()->eq('node.'.$tree['parent'], is_string($id) ? $qb->expr()->literal($id) : $id));
        } else {
            $qb->andWhere($qb->expr()->isNull('node.'.$tree['parent']));
        }
        return $qb;
    }

    /**
     * Get query for previous siblings of the given $node
     *
     * @param object $node
     * @param bool $includeSelf - include the node itself
     * @throws \Gedmo\Exception\InvalidArgumentException - if input is invalid
     * @return Doctrine\ORM\Query
     */
    public function getPrevSiblingsQuery($node, $includeSelf = false)
    {
        return $this->getPrevSiblingsQueryBuilder($node, $includeSelf)->getQuery();
    }

    /**
     * Find the previous siblings of the given $node
     *
     * @param object $node
     * @param bool $includeSelf - include the node itself
     * @return array
     */
    public function getPrevSiblings($node, $includeSelf = false)
    {
        return $this->getPrevSiblingsQuery($node, $includeSelf)->getResult();
    }

    /**
     * Move the node down in the same level
     *
     * @param object $node
     * @param mixed $number
     *         integer - number of positions to shift
     *         boolean - if "true" - shift till last position
     * @throws RuntimeException - if something fails in transaction
     * @return boolean - true if shifted
     */
    public function moveDown($node, $number = 1)
    {
        $result = false;
        $meta = $this->getClassMetadata();
        $nextSiblings = $this->getNextSiblings($node);
        if ($numSiblings = count($nextSiblings)) {
            $result = true;
            if ($number === true) {
                $number = $numSiblings;
            } elseif ($number > $numSiblings) {
                $number = $numSiblings;
            }
            $this->listener
                ->getStrategy($this->_em, $meta->name)
                ->updateNode($this->_em, $node, $nextSiblings[$number - 1], Nested::NEXT_SIBLING);
        }
        return $result;
    }

    /**
     * Move the node up in the same level
     *
     * @param object $node
     * @param mixed $number
     *         integer - number of positions to shift
     *         boolean - true shift till first position
     * @throws RuntimeException - if something fails in transaction
     * @return boolean - true if shifted
     */
    public function moveUp($node, $number = 1)
    {
        $result = false;
        $meta = $this->getClassMetadata();
        $prevSiblings = array_reverse($this->getPrevSiblings($node));
        if ($numSiblings = count($prevSiblings)) {
            $result = true;
            if ($number === true) {
                $number = $numSiblings;
            } elseif ($number > $numSiblings) {
                $number = $numSiblings;
            }
            $this->listener
                ->getStrategy($this->_em, $meta->name)
                ->updateNode($this->_em, $node, $prevSiblings[$number - 1], Nested::PREV_SIBLING);
        }
        return $result;
    }

    /**
     * UNSAFE: be sure to backup before runing this method when necessary
     *
     * Removes given $node from the tree and reparents its descendants
     *
     * @param object $node
     * @throws RuntimeException - if something fails in transaction
     * @return void
     */
    public function removeFromTree($node)
    {
        $meta = $this->getClassMetadata();
        if ($node instanceof $meta->name) {
            if (!$this->_em->getUnitOfWork()->isInIdentityMap($node)) {
                throw new InvalidArgumentException("Node is not managed by UnitOfWork");
            }

            $this->_em->initializeObject($node);
            $pk = $meta->getSingleIdentifierFieldName();
            $tree = $this->listener->getConfiguration($this->_em, $meta->name)->getMapping();
            $right = $meta->getReflectionProperty($tree['right'])->getValue($node);
            $left = $meta->getReflectionProperty($tree['left'])->getValue($node);
            $rootId = isset($tree['root']) ? $meta->getReflectionProperty($tree['root'])->getValue($node) : null;

            if ($right == $left + 1) {
                $this->removeSingle($node);
                $this->listener
                    ->getStrategy($this->_em, $meta->name)
                    ->shiftRL($this->_em, $meta->name, $right, -2, $rootId);
                return; // node was a leaf
            }
            // process updates in transaction
            $this->_em->getConnection()->beginTransaction();
            try {
                $parent = $meta->getReflectionProperty($tree['parent'])->getValue($node);
                $this->_em->initializeObject($node);
                $parentId = $parent ? $meta->getReflectionProperty($pk)->getValue($parent) : null;

                $nodeId = $meta->getReflectionProperty($pk)->getValue($node);
                $shift = -1;

                // in case if root node is removed, childs become roots
                if (isset($tree['root']) && !$parent) {
                    $qb = $this->_em->createQueryBuilder();
                    $nodes = $qb
                        ->select('node.'.$pk, 'node.'.$tree['left'], 'node.'.$tree['right'])
                        ->from($tree['rootClass'], 'node')
                        ->where($qb->expr()->eq(
                            'node.'.$tree['parent'],
                            is_string($nodeId) ? $qb->expr()->literal($nodeId) : $nodeId
                        ))
                        ->getQuery()
                        ->getArrayResult();

                    foreach ($nodes as $newRoot) {
                        $left = $newRoot[$tree['left']];
                        $right = $newRoot[$tree['right']];
                        $rootId = $newRoot[$pk];
                        $shift = -($left - 1);

                        $qb = $this->_em->createQueryBuilder();
                        $qb->update($tree['rootClass'], 'node')
                            ->set(
                                'node.'.$tree['root'],
                                $rootId === null ? 'NULL' : (is_string($rootId) ? $qb->expr()->literal($rootId) : $rootId)
                            )
                            ->where($qb->expr()->eq('node.'.$tree['root'], is_string($nodeId) ? $qb->expr()->literal($nodeId) : $nodeId))
                            ->andWhere($qb->expr()->gte('node.'.$tree['left'], $left))
                            ->andWhere($qb->expr()->lte('node.'.$tree['right'], $right))
                        ;
                        $qb->getQuery()->getSingleScalarResult();

                        $qb = $this->_em->createQueryBuilder();
                        $qb->update($tree['rootClass'], 'node')
                            ->set(
                                'node.'.$tree['parent'],
                                $parentId === null ? 'NULL' : (is_string($parentId) ? $qb->expr()->literal($parentId) : $parentId)
                            )
                            ->where($nodeId === null ?
                                $qb->expr()->isNull('node.'.$tree['parent']) :
                                $qb->expr()->eq('node.'.$tree['parent'], is_string($nodeId) ? $qb->expr()->literal($nodeId) : $nodeId)
                            )
                            ->andWhere($rootId === null ?
                                $qb->expr()->isNull('node.'.$tree['root']) :
                                $qb->expr()->eq('node.'.$tree['root'], is_string($rootId) ? $qb->expr()->literal($rootId) : $rootId)
                            )
                        ;
                        $qb->getQuery()->getSingleScalarResult();

                        $this->listener
                            ->getStrategy($this->_em, $meta->name)
                            ->shiftRangeRL($this->_em, $meta->name, $left, $right, $shift, $rootId, $rootId, - 1);
                        $this->listener
                            ->getStrategy($this->_em, $meta->name)
                            ->shiftRL($this->_em, $meta->name, $right, -2, $rootId);
                    }
                } else {
                    $qb = $this->_em->createQueryBuilder();
                    $qb->update($tree['rootClass'], 'node')
                        ->set(
                            'node.'.$tree['parent'],
                            $parentId === null ? 'NULL' : (is_string($parentId) ? $qb->expr()->literal($parentId) : $parentId)
                        )
                        ->where($nodeId === null ?
                            $qb->expr()->isNull('node.'.$tree['parent']) :
                            $qb->expr()->eq('node.'.$tree['parent'], is_string($nodeId) ? $qb->expr()->literal($nodeId) : $nodeId)
                        );
                    if (isset($tree['root'])) {
                        $qb->andWhere($rootId === null ?
                            $qb->expr()->isNull('node.'.$tree['root']) :
                            $qb->expr()->eq('node.'.$tree['root'], is_string($rootId) ? $qb->expr()->literal($rootId) : $rootId)
                        );
                    }
                    $qb->getQuery()->getSingleScalarResult();

                    $this->listener
                        ->getStrategy($this->_em, $meta->name)
                        ->shiftRangeRL($this->_em, $meta->name, $left, $right, $shift, $rootId, $rootId, - 1);

                    $this->listener
                        ->getStrategy($this->_em, $meta->name)
                        ->shiftRL($this->_em, $meta->name, $right, -2, $rootId);
                }
                $this->removeSingle($node);
                $this->_em->getConnection()->commit();
            } catch (\Exception $e) {
                $this->_em->getConnection()->rollback();
                $this->_em->close();
                throw $e;
            }
        } else {
            throw new InvalidArgumentException("Node is not related to this repository");
        }
    }

    /**
     * Reorders $node's sibling nodes and child nodes,
     * according to the $sortByField and $direction specified
     *
     * @param object|null $node - node from which to start reordering the tree; null will reorder everything
     * @param string $sortByField - field name to sort by
     * @param string $direction - sort direction : "ASC" or "DESC"
     * @param boolean $verify - true to verify tree first
     * @return void
     */
    public function reorder($node, $sortByField = null, $direction = 'ASC', $verify = true)
    {
        $meta = $this->getClassMetadata();
        $tree = $this->listener->getConfiguration($this->_em, $meta->name)->getMapping();
        if ($verify && is_array($this->verify())) {
            return false;
        }

        $nodes = $this->children($node, true, $sortByField, $direction);
        foreach ($nodes as $node) {
            $this->_em->initializeObject($node);
            $right = $meta->getReflectionProperty($tree['right'])->getValue($node);
            $left = $meta->getReflectionProperty($tree['left'])->getValue($node);
            $this->moveDown($node, true);
            if ($left !== $right - 1) {
                $this->reorder($node, $sortByField, $direction, false);
            }
        }
    }

    /**
     * Reorders all nodes in the tree according to the $sortByField and $direction specified.
     *
     * @param string $sortByField - field name to sort by
     * @param string $direction - sort direction : "ASC" or "DESC"
     * @param boolean $verify - true to verify tree first
     * @return void
     */
    public function reorderAll($sortByField = null, $direction = 'ASC', $verify = true)
    {
        $this->reorder(null, $sortByField, $direction, $verify);
    }

    /**
     * Verifies that current tree is valid.
     * If any error is detected it will return an array
     * with a list of errors found on tree
     *
     * @return mixed
     *         boolean - true on success
     *         array - error list on failure
     */
    public function verify()
    {
        if (!$this->childCount()) {
            return true; // tree is empty
        }

        $errors = array();
        $meta = $this->getClassMetadata();
        $tree = $this->listener->getConfiguration($this->_em, $meta->name)->getMapping();
        if (isset($tree['root'])) {
            $trees = $this->getRootNodes();
            foreach ($trees as $tree) {
                $this->verifyTree($errors, $tree);
            }
        } else {
            $this->verifyTree($errors);
        }

        return $errors ?: true;
    }

    /**
     * NOTE: flush your entity manager after
     *
     * Tries to recover the tree
     *
     * @return void
     */
    public function recover()
    {
        if ($this->verify() === true) {
            return;
        }
        $meta = $this->getClassMetadata();
        $tree = $this->listener->getConfiguration($this->_em, $meta->name)->getMapping();
        $self = $this;
        $em = $this->_em;

        $doRecover = function($root, &$count) use($meta, $tree, $self, $em, &$doRecover) {
            $lft = $count++;
            foreach ($self->getChildren($root, true) as $child) {
                $doRecover($child, $count);
            }
            $rgt = $count++;
            $meta->getReflectionProperty($tree['left'])->setValue($root, $lft);
            $meta->getReflectionProperty($tree['right'])->setValue($root, $rgt);
            $this->_em->persist($root);
        };

        if (isset($tree['root'])) {
            foreach ($this->getRootNodes() as $root) {
                $count = 1; // reset on every root node
                $doRecover($root, $count);
            }
        } else {
            $count = 1;
            foreach($this->getChildren(null, true) as $root) {
                $doRecover($root, $count);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getNodesHierarchyQueryBuilder($node = null, $direct = false, array $options = array(), $includeNode = false)
    {
        $meta = $this->getClassMetadata();
        $tree = $this->listener->getConfiguration($this->_em, $meta->name)->getMapping();

        return $this->childrenQueryBuilder(
            $node,
            $direct,
            isset($tree['root']) ? array($tree['root'], $tree['left']) : $tree['left'],
            'ASC',
            $includeNode
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getNodesHierarchyQuery($node = null, $direct = false, array $options = array(), $includeNode = false)
    {
        return $this->getNodesHierarchyQueryBuilder($node, $direct, $options, $includeNode)->getQuery();
    }

    /**
     * {@inheritdoc}
     */
    public function getNodesHierarchy($node = null, $direct = false, array $options = array(), $includeNode = false)
    {
        return $this->getNodesHierarchyQuery($node, $direct, $options, $includeNode)->getArrayResult();
    }

    /**
     * {@inheritdoc}
     */
    protected function validate()
    {
        return $this->listener->getStrategy($this->_em, $this->getClassMetadata()->name)->getName() === Strategy::NESTED;
    }

    /**
     * Collect errors on given tree if
     * where are any
     *
     * @param array $errors
     * @param object $root
     * @return void
     */
    private function verifyTree(&$errors, $root = null)
    {
        $meta = $this->getClassMetadata();
        $tree = $this->listener->getConfiguration($this->_em, $meta->name)->getMapping();

        $identifier = $meta->getSingleIdentifierFieldName();
        $rootId = isset($tree['root']) ? $meta->getReflectionProperty($tree['root'])->getValue($root) : null;
        $qb = $this->_em->createQueryBuilder();
        $qb->select($qb->expr()->min('node.'.$tree['left']))
            ->from($tree['rootClass'], 'node')
        ;
        if (isset($tree['root'])) {
            $qb->where($rootId === null ?
                $qb->expr()->isNull('node.'.$tree['root']) :
                $qb->expr()->eq('node.'.$tree['root'], is_string($rootId) ? $qb->expr()->literal($rootId) : $rootId)
            );
        }
        $min = intval($qb->getQuery()->getSingleScalarResult());
        $edge = $this->listener->getStrategy($this->_em, $meta->name)->max($this->_em, $meta->name, $rootId);
        // check duplicate right and left values
        for ($i = $min; $i <= $edge; $i++) {
            $qb = $this->_em->createQueryBuilder();
            $qb->select($qb->expr()->count('node.'.$identifier))
                ->from($tree['rootClass'], 'node')
                ->where($qb->expr()->orX(
                    $qb->expr()->eq('node.'.$tree['left'], $i),
                    $qb->expr()->eq('node.'.$tree['right'], $i)
                ))
            ;
            if (isset($tree['root'])) {
                $qb->andWhere($rootId === null ?
                    $qb->expr()->isNull('node.'.$tree['root']) :
                    $qb->expr()->eq('node.'.$tree['root'], is_string($rootId) ? $qb->expr()->literal($rootId) : $rootId)
                );
            }
            $count = intval($qb->getQuery()->getSingleScalarResult());
            if ($count !== 1) {
                if ($count === 0) {
                    $errors[] = "index [{$i}], missing" . ($root ? ' on tree root: ' . $rootId : '');
                } else {
                    $errors[] = "index [{$i}], duplicate" . ($root ? ' on tree root: ' . $rootId : '');
                }
            }
        }
        // check for missing parents
        $qb = $this->_em->createQueryBuilder();
        $qb->select('node')
            ->from($tree['rootClass'], 'node')
            ->leftJoin('node.'.$tree['parent'], 'parent')
            ->where($qb->expr()->isNotNull('node.'.$tree['parent']))
            ->andWhere($qb->expr()->isNull('parent.'.$identifier))
        ;
        if (isset($tree['root'])) {
            $qb->andWhere($rootId === null ?
                $qb->expr()->isNull('node.'.$tree['root']) :
                $qb->expr()->eq('node.'.$tree['root'], is_string($rootId) ? $qb->expr()->literal($rootId) : $rootId)
            );
        }
        $nodes = $qb->getQuery()->getArrayResult();
        if (count($nodes)) {
            foreach ($nodes as $node) {
                $errors[] = "node [{$node[$identifier]}] has missing parent" . ($root ? ' on tree root: ' . $rootId : '');
            }
            return; // loading broken relation can cause infinite loop
        }

        $qb = $this->_em->createQueryBuilder();
        $qb->select('node')
            ->from($tree['rootClass'], 'node')
            ->where($qb->expr()->lt('node.'.$tree['right'], 'node.'.$tree['left']))
        ;
        if (isset($tree['root'])) {
            $qb->andWhere($rootId === null ?
                $qb->expr()->isNull('node.'.$tree['root']) :
                $qb->expr()->eq('node.'.$tree['root'], is_string($rootId) ? $qb->expr()->literal($rootId) : $rootId)
            );
        }
        $result = $qb->getQuery()
            ->setMaxResults(1)
            ->getResult(Query::HYDRATE_ARRAY);
        $node = count($result) ? array_shift($result) : null;

        if ($node) {
            $id = $node[$identifier];
            $errors[] = "node [{$id}], left is greater than right" . ($root ? ' on tree root: ' . $rootId : '');
        }

        $qb = $this->_em->createQueryBuilder();
        $qb->select('node')
            ->from($tree['rootClass'], 'node')
        ;
        if (isset($tree['root'])) {
            $qb->where($rootId === null ?
                $qb->expr()->isNull('node.'.$tree['root']) :
                $qb->expr()->eq('node.'.$tree['root'], is_string($rootId) ? $qb->expr()->literal($rootId) : $rootId)
            );
        }
        $nodes = $qb->getQuery()->getResult(Query::HYDRATE_OBJECT);

        foreach ($nodes as $node) {
            $right = $meta->getReflectionProperty($tree['right'])->getValue($node);
            $left = $meta->getReflectionProperty($tree['left'])->getValue($node);
            $id = $meta->getReflectionProperty($identifier)->getValue($node);
            $parent = $meta->getReflectionProperty($tree['parent'])->getValue($node);
            if (!$right || !$left) {
                $errors[] = "node [{$id}] has invalid left or right values";
            } elseif ($right == $left) {
                $errors[] = "node [{$id}] has identical left and right values";
            } elseif ($parent) {
                $this->_em->initializeObject($parent);
                $parentRight = $meta->getReflectionProperty($tree['right'])->getValue($parent);
                $parentLeft = $meta->getReflectionProperty($tree['left'])->getValue($parent);
                $parentId = $meta->getReflectionProperty($identifier)->getValue($parent);
                if ($left < $parentLeft) {
                    $errors[] = "node [{$id}] left is less than parent`s [{$parentId}] left value";
                } elseif ($right > $parentRight) {
                    $errors[] = "node [{$id}] right is greater than parent`s [{$parentId}] right value";
                }
            } else {
                $qb = $this->_em->createQueryBuilder();
                $qb->select($qb->expr()->count('node.'.$identifier))
                    ->from($tree['rootClass'], 'node')
                    ->where($qb->expr()->lt('node.'.$tree['left'], $left))
                    ->andWhere($qb->expr()->gt('node.'.$tree['right'], $right))
                ;
                if (isset($tree['root'])) {
                    $qb->andWhere($rootId === null ?
                        $qb->expr()->isNull('node.'.$tree['root']) :
                        $qb->expr()->eq('node.'.$tree['root'], is_string($rootId) ? $qb->expr()->literal($rootId) : $rootId)
                    );
                }
                if ($count = intval($qb->getQuery()->getSingleScalarResult())) {
                    $errors[] = "node [{$id}] parent field is blank, but it has a parent";
                }
            }
        }
    }

    /**
     * Removes single node without touching children
     *
     * @internal
     * @param EntityWrapper $wrapped
     * @return void
     */
    private function removeSingle($node)
    {
        $meta = $this->getClassMetadata();
        $tree = $this->listener->getConfiguration($this->_em, $meta->name)->getMapping();

        $this->_em->initializeObject($node);
        $pk = $meta->getSingleIdentifierFieldName();
        $nodeId = $meta->getReflectionProperty($pk)->getValue($node);
        // prevent from deleting whole branch
        $qb = $this->_em->createQueryBuilder();
        $qb->update($tree['rootClass'], 'node')
            ->set('node.'.$tree['left'], 0)
            ->set('node.'.$tree['right'], 0)
            ->where($nodeId === null ?
                $qb->expr()->isNull('node.'.$pk) :
                $qb->expr()->eq('node.'.$pk, is_string($nodeId) ? $qb->expr()->literal($nodeId) : $nodeId)
            )
        ;
        $qb->getQuery()->getSingleScalarResult();

        // remove the node from database
        $qb = $this->_em->createQueryBuilder();
        $qb->delete($tree['rootClass'], 'node')
            ->where($nodeId === null ?
                $qb->expr()->isNull('node.'.$pk) :
                $qb->expr()->eq('node.'.$pk, is_string($nodeId) ? $qb->expr()->literal($nodeId) : $nodeId)
            )
        ;
        $qb->getQuery()->getSingleScalarResult();
        // remove from identity map
        $this->_em->getUnitOfWork()->removeFromIdentityMap($node);
    }
}
