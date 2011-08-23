<?php

namespace Gedmo\Tree\Entity\Repository;

use Gedmo\Tool\Wrapper\EntityWrapper;
use Doctrine\ORM\Query,
    Gedmo\Tree\Strategy,
    Gedmo\Tree\Strategy\ORM\Nested,
    Gedmo\Exception\InvalidArgumentException,
    Doctrine\ORM\Proxy\Proxy;

/**
 * The NestedTreeRepository has some useful functions
 * to interact with NestedSet tree. Repository uses
 * the strategy used by listener
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree.Entity.Repository
 * @subpackage NestedTreeRepository
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class NestedTreeRepository extends AbstractTreeRepository
{
    /**
     * Get all root nodes query
     *
     * @return Query
     */
    public function getRootNodesQuery()
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->_em, $meta->name);
        $qb = $this->_em->createQueryBuilder();
        $qb->select('node')
            ->from($config['useObjectClass'], 'node')
            ->where('node.' . $config['parent'] . " IS NULL")
            ->orderBy('node.' . $config['left'], 'ASC');
        return $qb->getQuery();
    }

    /**
     * Get all root nodes
     *
     * @return array
     */
    public function getRootNodes()
    {
        return $this->getRootNodesQuery()->getResult();
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
            $wrapped = new EntityWrapper($node, $this->_em);
            $meta = $this->getClassMetadata();
            $config = $this->listener->getConfiguration($this->_em, $meta->name);
            $position = substr($method, 9);
            if (substr($method, -2) === 'Of') {
                if (!isset($args[1])) {
                    throw new \Gedmo\Exception\InvalidArgumentException('If "Of" is specified you must provide parent or sibling as the second argument');
                }
                $parent = $args[1];
                $wrapped->setPropertyValue($config['parent'], $parent);
                $position = substr($position, 0, -2);
            }
            $wrapped->setPropertyValue($config['left'], 0); // simulate changeset
            $oid = spl_object_hash($node);
            $this->listener
                ->getStrategy($this->_em, $meta->name)
                ->setNodePosition($oid, $position);

            $this->_em->persist($node);
            return $this;
        }
        return parent::__call($method, $args);
    }

    /**
     * Get the Tree path query by given $node
     *
     * @param object $node
     * @throws InvalidArgumentException - if input is not valid
     * @return Query
     */
    public function getPathQuery($node)
    {
        $meta = $this->getClassMetadata();
        if (!$node instanceof $meta->name) {
            throw new InvalidArgumentException("Node is not related to this repository");
        }
        $config = $this->listener->getConfiguration($this->_em, $meta->name);
        $wrapped = new EntityWrapper($node, $this->_em);
        if (!$wrapped->hasValidIdentifier()) {
            throw new InvalidArgumentException("Node is not managed by UnitOfWork");
        }
        $left = $wrapped->getPropertyValue($config['left']);
        $right = $wrapped->getPropertyValue($config['right']);
        $qb = $this->_em->createQueryBuilder();
        $qb->select('node')
            ->from($config['useObjectClass'], 'node')
            ->where('node.' . $config['left'] . " <= :left")
            ->andWhere('node.' . $config['right'] . " >= :right")
            ->orderBy('node.' . $config['left'], 'ASC');
        if (isset($config['root'])) {
            $rootId = $wrapped->getPropertyValue($config['root']);
            $qb->andWhere("node.{$config['root']} = {$rootId}");
        }
        $q = $qb->getQuery();
        $q->setParameters(compact('left', 'right'));
        return $q;
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
     * Counts the children of given TreeNode
     *
     * @param object $node - if null counts all records in tree
     * @param boolean $direct - true to count only direct children
     * @throws InvalidArgumentException - if input is not valid
     * @return integer
     */
    public function childCount($node = null, $direct = false)
    {
        $count = 0;
        $meta = $this->getClassMetadata();
        $nodeId = $meta->getSingleIdentifierFieldName();
        $config = $this->listener->getConfiguration($this->_em, $meta->name);
        if (null !== $node) {
            if ($node instanceof $meta->name) {
                $wrapped = new EntityWrapper($node, $this->_em);
                if (!$wrapped->hasValidIdentifier()) {
                    throw new InvalidArgumentException("Node is not managed by UnitOfWork");
                }
                if ($direct) {
                    $id = $wrapped->getIdentifier();
                    $qb = $this->_em->createQueryBuilder();
                    $qb->select('COUNT(node.' . $nodeId . ')')
                        ->from($config['useObjectClass'], 'node')
                        ->where('node.' . $config['parent'] . ' = ' . $id);

                    if (isset($config['root'])) {
                        $rootId = $wrapped->getPropertyValue($config['root']);
                        $qb->andWhere("node.{$config['root']} = {$rootId}");
                    }
                    $q = $qb->getQuery();
                    $count = intval($q->getSingleScalarResult());
                } else {
                    $left = $wrapped->getPropertyValue($config['left']);
                    $right = $wrapped->getPropertyValue($config['right']);
                    if ($left && $right) {
                        $count = ($right - $left - 1) / 2;
                    }
                }
            } else {
                throw new InvalidArgumentException("Node is not related to this repository");
            }
        } else {
            $dql = "SELECT COUNT(node.{$nodeId}) FROM " . $config['useObjectClass'] . " node";
            if ($direct) {
                $dql .= ' WHERE node.' . $config['parent'] . ' IS NULL';
            }
            $q = $this->_em->createQuery($dql);
            $count = intval($q->getSingleScalarResult());
        }
        return $count;
    }

    /**
     * Get tree children query followed by given $node
     *
     * @param object $node - if null, all tree nodes will be taken
     * @param boolean $direct - true to take only direct children
     * @param string $sortByField - field name to sort by
     * @param string $direction - sort direction : "ASC" or "DESC"
     * @throws InvalidArgumentException - if input is not valid
     * @return Query
     */
    public function childrenQuery($node = null, $direct = false, $sortByField = null, $direction = 'ASC')
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->_em, $meta->name);

        $qb = $this->_em->createQueryBuilder();
        $qb->select('node')
            ->from($config['useObjectClass'], 'node');
        if ($node !== null) {
            if ($node instanceof $meta->name) {
                $wrapped = new EntityWrapper($node, $this->_em);
                if (!$wrapped->hasValidIdentifier()) {
                    throw new InvalidArgumentException("Node is not managed by UnitOfWork");
                }
                if ($direct) {
                    $id = $wrapped->getIdentifier();
                    $qb->where('node.' . $config['parent'] . ' = ' . $id);
                } else {
                    $left = $wrapped->getPropertyValue($config['left']);
                    $right = $wrapped->getPropertyValue($config['right']);
                    if ($left && $right) {
                        $qb->where('node.' . $config['right'] . " < {$right}")
                            ->andWhere('node.' . $config['left'] . " > {$left}");
                    }
                }
                if (isset($config['root'])) {
                    $rootId = $wrapped->getPropertyValue($config['root']);
                    $qb->andWhere("node.{$config['root']} = {$rootId}");
                }
            } else {
                throw new \InvalidArgumentException("Node is not related to this repository");
            }
        } else {
            if ($direct) {
                $qb->where('node.' . $config['parent'] . ' IS NULL');
            }
        }
        if (!$sortByField) {
            $qb->orderBy('node.' . $config['left'], 'ASC');
        } else {
            if ($meta->hasField($sortByField) && in_array(strtolower($direction), array('asc', 'desc'))) {
                $qb->orderBy('node.' . $sortByField, $direction);
            } else {
                throw new InvalidArgumentException("Invalid sort options specified: field - {$sortByField}, direction - {$direction}");
            }
        }
        return $qb->getQuery();
    }

    /**
     * Get list of children followed by given $node
     *
     * @param object $node - if null, all tree nodes will be taken
     * @param boolean $direct - true to take only direct children
     * @param string $sortByField - field name to sort by
     * @param string $direction - sort direction : "ASC" or "DESC"
     * @return array - list of given $node children, null on failure
     */
    public function children($node = null, $direct = false, $sortByField = null, $direction = 'ASC')
    {
        $q = $this->childrenQuery($node, $direct, $sortByField, $direction);
        return $q->getResult();
    }

    /**
     * Get tree leafs query
     *
     * @param object $root - root node in case of root tree is required
     * @param string $sortByField - field name to sort by
     * @param string $direction - sort direction : "ASC" or "DESC"
     * @throws InvalidArgumentException - if input is not valid
     * @return Query
     */
    public function getLeafsQuery($root = null, $sortByField = null, $direction = 'ASC')
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->_em, $meta->name);

        if (isset($config['root']) && is_null($root)) {
            if (is_null($root)) {
                throw new InvalidArgumentException("If tree has root, getLeafs method requires any node of this tree");
            }
        }

        $qb = $this->_em->createQueryBuilder();
        $qb->select('node')
            ->from($config['useObjectClass'], 'node')
            ->where('node.' . $config['right'] . ' = 1 + node.' . $config['left']);
        if (isset($config['root'])) {
            if ($root instanceof $meta->name) {
                $wrapped = new EntityWrapper($root, $this->_em);
                $rootId = $wrapped->getPropertyValue($config['root']);
                if (!$rootId) {
                    throw new InvalidArgumentException("Root node must be managed");
                }
                $qb->andWhere("node.{$config['root']} = {$rootId}");
            } else {
                throw new InvalidArgumentException("Node is not related to this repository");
            }
        }
        if (!$sortByField) {
            $qb->orderBy('node.' . $config['left'], 'ASC');
        } else {
            if ($meta->hasField($sortByField) && in_array(strtolower($direction), array('asc', 'desc'))) {
                $qb->orderBy('node.' . $sortByField, $direction);
            } else {
                throw new InvalidArgumentException("Invalid sort options specified: field - {$sortByField}, direction - {$direction}");
            }
        }
        return $qb->getQuery();
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
     * Get the query for next siblings of the given $node
     *
     * @param object $node
     * @param bool $includeSelf - include the node itself
     * @throws \Gedmo\Exception\InvalidArgumentException - if input is invalid
     * @return Query
     */
    public function getNextSiblingsQuery($node, $includeSelf = false)
    {
        $meta = $this->getClassMetadata();
        if (!$node instanceof $meta->name) {
            throw new InvalidArgumentException("Node is not related to this repository");
        }
        $wrapped = new EntityWrapper($node, $this->_em);
        if (!$wrapped->hasValidIdentifier()) {
            throw new InvalidArgumentException("Node is not managed by UnitOfWork");
        }

        $config = $this->listener->getConfiguration($this->_em, $meta->name);
        $parent = $wrapped->getPropertyValue($config['parent']);
        if (isset($config['root']) && !$parent) {
            throw new InvalidArgumentException("Cannot get siblings from tree root node");
        }

        $left = $wrapped->getPropertyValue($config['left']);
        $sign = $includeSelf ? '>=' : '>';

        $dql = "SELECT node FROM {$config['useObjectClass']} node";
        if ($parent) {
            $wrappedParent = new EntityWrapper($parent, $this->_em);
            $parentId = $wrappedParent->getIdentifier();
            $dql .= " WHERE node.{$config['parent']} = {$parentId}";
        } else {
            $dql .= " WHERE node.{$config['parent']} IS NULL";
        }
        $dql .= " AND node.{$config['left']} {$sign} {$left}";
        $dql .= " ORDER BY node.{$config['left']} ASC";
        return $this->_em->createQuery($dql);
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
     * Get query for previous siblings of the given $node
     *
     * @param object $node
     * @param bool $includeSelf - include the node itself
     * @throws \Gedmo\Exception\InvalidArgumentException - if input is invalid
     * @return Query
     */
    public function getPrevSiblingsQuery($node, $includeSelf = false)
    {
        $meta = $this->getClassMetadata();
        if (!$node instanceof $meta->name) {
            throw new InvalidArgumentException("Node is not related to this repository");
        }
        $wrapped = new EntityWrapper($node, $this->_em);
        if (!$wrapped->hasValidIdentifier()) {
            throw new InvalidArgumentException("Node is not managed by UnitOfWork");
        }

        $config = $this->listener->getConfiguration($this->_em, $meta->name);
        $parent = $wrapped->getPropertyValue($config['parent']);
        if (isset($config['root']) && !$parent) {
            throw new InvalidArgumentException("Cannot get siblings from tree root node");
        }

        $left = $wrapped->getPropertyValue($config['left']);
        $sign = $includeSelf ? '<=' : '<';

        $dql = "SELECT node FROM {$config['useObjectClass']} node";
        if ($parent) {
            $wrappedParent = new EntityWrapper($parent, $this->_em);
            $parentId = $wrappedParent->getIdentifier();
            $dql .= " WHERE node.{$config['parent']} = {$parentId}";
        } else {
            $dql .= " WHERE node.{$config['parent']} IS NULL";
        }
        $dql .= " AND node.{$config['left']} {$sign} {$left}";
        $dql .= " ORDER BY node.{$config['left']} ASC";
        return $this->_em->createQuery($dql);
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
        if ($node instanceof $meta->name) {
            $config = $this->listener->getConfiguration($this->_em, $meta->name);
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
        } else {
            throw new InvalidArgumentException("Node is not related to this repository");
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
        if ($node instanceof $meta->name) {
            $config = $this->listener->getConfiguration($this->_em, $meta->name);
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
        } else {
            throw new InvalidArgumentException("Node is not related to this repository");
        }
        return $result;
    }

    /**
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
            $wrapped = new EntityWrapper($node, $this->_em);
            $config = $this->listener->getConfiguration($this->_em, $meta->name);
            $right = $wrapped->getPropertyValue($config['right']);
            $left = $wrapped->getPropertyValue($config['left']);
            $rootId = isset($config['root']) ? $wrapped->getPropertyValue($config['root']) : null;

            if ($right == $left + 1) {
                $this->removeSingle($wrapped);
                $this->listener
                    ->getStrategy($this->_em, $meta->name)
                    ->shiftRL($this->_em, $config['useObjectClass'], $right, -2, $rootId);
                return; // node was a leaf
            }
            // process updates in transaction
            $this->_em->getConnection()->beginTransaction();
            try {
                $parent = $wrapped->getPropertyValue($config['parent']);
                $parentId = 'NULL';
                if ($parent) {
                    $wrappedParrent = new EntityWrapper($parent, $this->_em);
                    $parentId = $wrappedParrent->getIdentifier();
                }
                $pk = $meta->getSingleIdentifierFieldName();
                $nodeId = $wrapped->getIdentifier();
                $shift = -1;

                // in case if root node is removed, childs become roots
                if (isset($config['root']) && !$parent) {
                    $dql = "SELECT node.{$pk}, node.{$config['left']}, node.{$config['right']} FROM {$config['useObjectClass']} node";
                    $dql .= " WHERE node.{$config['parent']} = {$nodeId}";
                    $nodes = $this->_em->createQuery($dql)->getArrayResult();

                    foreach ($nodes as $newRoot) {
                        $left = $newRoot[$config['left']];
                        $right = $newRoot[$config['right']];
                        $rootId = $newRoot[$pk];
                        $shift = -($left - 1);

                        $dql = "UPDATE {$config['useObjectClass']} node";
                        $dql .= ' SET node.' . $config['root'] . ' = :rootId';
                        $dql .= ' WHERE node.' . $config['root'] . ' = :nodeId';
                        $dql .= " AND node.{$config['left']} >= :left";
                        $dql .= " AND node.{$config['right']} <= :right";

                        $q = $this->_em->createQuery($dql);
                        $q->setParameters(compact('rootId', 'left', 'right', 'nodeId'));
                        $q->getSingleScalarResult();

                        $dql = "UPDATE {$config['useObjectClass']} node";
                        $dql .= ' SET node.' . $config['parent'] . ' = ' . $parentId;
                        $dql .= ' WHERE node.' . $config['parent'] . ' = ' . $nodeId;
                        $dql .= ' AND node.' . $config['root'] . ' = ' . $rootId;

                        $q = $this->_em->createQuery($dql);
                        $q->getSingleScalarResult();

                        $this->listener
                            ->getStrategy($this->_em, $meta->name)
                            ->shiftRangeRL($this->_em, $config['useObjectClass'], $left, $right, $shift, $rootId, $rootId, - 1);
                        $this->listener
                            ->getStrategy($this->_em, $meta->name)
                            ->shiftRL($this->_em, $config['useObjectClass'], $right, -2, $rootId);
                    }
                } else {
                    $dql = "UPDATE {$config['useObjectClass']} node";
                    $dql .= ' SET node.' . $config['parent'] . ' = ' . $parentId;
                    $dql .= ' WHERE node.' . $config['parent'] . ' = ' . $nodeId;
                    if (isset($config['root'])) {
                        $dql .= ' AND node.' . $config['root'] . ' = ' . $rootId;
                    }
                    // @todo: update in memory nodes
                    $q = $this->_em->createQuery($dql);
                    $q->getSingleScalarResult();

                    $this->listener
                        ->getStrategy($this->_em, $meta->name)
                        ->shiftRangeRL($this->_em, $config['useObjectClass'], $left, $right, $shift, $rootId, $rootId, - 1);

                    $this->listener
                        ->getStrategy($this->_em, $meta->name)
                        ->shiftRL($this->_em, $config['useObjectClass'], $right, -2, $rootId);
                }
                $this->removeSingle($wrapped);
                $this->_em->getConnection()->commit();
            } catch (\Exception $e) {
                $this->_em->close();
                $this->_em->getConnection()->rollback();
                throw new \Gedmo\Exception\RuntimeException('Transaction failed', null, $e);
            }
        } else {
            throw new InvalidArgumentException("Node is not related to this repository");
        }
    }

    /**
     * Reorders the sibling nodes and child nodes by given $node,
     * according to the $sortByField and $direction specified
     *
     * @param object $node - from which node to start reordering the tree
     * @param string $sortByField - field name to sort by
     * @param string $direction - sort direction : "ASC" or "DESC"
     * @param boolean $verify - true to verify tree first
     * @return void
     */
    public function reorder($node, $sortByField = null, $direction = 'ASC', $verify = true)
    {
        $meta = $this->getClassMetadata();
        if ($node instanceof $meta->name) {
            $config = $this->listener->getConfiguration($this->_em, $meta->name);
            if ($verify && is_array($this->verify())) {
                return false;
            }

            $nodes = $this->children($node, true, $sortByField, $direction);
            foreach ($nodes as $node) {
                $wrapped = new EntityWrapper($node, $this->_em);
                $right = $wrapped->getPropertyValue($config['right']);
                $left = $wrapped->getPropertyValue($config['left']);
                $this->moveDown($node, true);
                if ($left != ($right - 1)) {
                    $this->reorder($node, $sortByField, $direction, false);
                }
            }
        } else {
            throw new InvalidArgumentException("Node is not related to this repository");
        }
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
        $config = $this->listener->getConfiguration($this->_em, $meta->name);
        if (isset($config['root'])) {
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
     * Tries to recover the tree
     *
     * @todo implement
     * @throws RuntimeException - if something fails in transaction
     * @return void
     */
    public function recover()
    {
        if ($this->verify() === true) {
            return;
        }
        // not yet implemented
    }

    /**
     * {@inheritdoc}
     */
    protected function validates()
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
        $config = $this->listener->getConfiguration($this->_em, $meta->name);

        $identifier = $meta->getSingleIdentifierFieldName();
        $rootId = isset($config['root']) ? $meta->getReflectionProperty($config['root'])->getValue($root) : null;

        $dql = "SELECT MIN(node.{$config['left']}) FROM {$config['useObjectClass']} node";
        if ($root) {
            $dql .= " WHERE node.{$config['root']} = {$rootId}";
        }
        $min = intval($this->_em->createQuery($dql)->getSingleScalarResult());
        $edge = $this->listener->getStrategy($this->_em, $meta->name)->max($this->_em, $config['useObjectClass'], $rootId);
        // check duplicate right and left values
        for ($i = $min; $i <= $edge; $i++) {
            $dql = "SELECT COUNT(node.{$identifier}) FROM {$config['useObjectClass']} node";
            $dql .= " WHERE (node.{$config['left']} = {$i} OR node.{$config['right']} = {$i})";
            if ($root) {
                $dql .= " AND node.{$config['root']} = {$rootId}";
            }
            $count = intval($this->_em->createQuery($dql)->getSingleScalarResult());
            if ($count !== 1) {
                if ($count === 0) {
                    $errors[] = "index [{$i}], missing" . ($root ? ' on tree root: ' . $rootId : '');
                } else {
                    $errors[] = "index [{$i}], duplicate" . ($root ? ' on tree root: ' . $rootId : '');
                }
            }
        }

        // check for missing parents
        $dql = "SELECT node FROM {$config['useObjectClass']} node";
        $dql .= " LEFT JOIN node.{$config['parent']} parent";
        $dql .= " WHERE node.{$config['parent']} IS NOT NULL";
        $dql .= " AND parent.{$identifier} IS NULL";
        if ($root) {
            $dql .= " AND node.{$config['root']} = {$rootId}";
        }
        $nodes = $this->_em->createQuery($dql)->getArrayResult();
        if (count($nodes)) {
            foreach ($nodes as $node) {
                $errors[] = "node [{$node[$identifier]}] has missing parent" . ($root ? ' on tree root: ' . $rootId : '');
            }
            return; // loading broken relation can cause infinite loop
        }

        $dql = "SELECT node FROM {$config['useObjectClass']} node";
        $dql .= " WHERE node.{$config['right']} < node.{$config['left']}";
        if ($root) {
            $dql .= " AND node.{$config['root']} = {$rootId}";
        }
        $result = $this->_em->createQuery($dql)
            ->setMaxResults(1)
            ->getResult(Query::HYDRATE_ARRAY);
        $node = count($result) ? array_shift($result) : null;

        if ($node) {
            $id = $node[$identifier];
            $errors[] = "node [{$id}], left is greater than right" . ($root ? ' on tree root: ' . $rootId : '');
        }

        $dql = "SELECT node FROM {$config['useObjectClass']} node";
        if ($root) {
            $dql .= " WHERE node.{$config['root']} = {$rootId}";
        }
        $nodes = $this->_em->createQuery($dql)->getResult(Query::HYDRATE_OBJECT);

        foreach ($nodes as $node) {
            $right = $meta->getReflectionProperty($config['right'])->getValue($node);
            $left = $meta->getReflectionProperty($config['left'])->getValue($node);
            $id = $meta->getReflectionProperty($identifier)->getValue($node);
            $parent = $meta->getReflectionProperty($config['parent'])->getValue($node);
            if (!$right || !$left) {
                $errors[] = "node [{$id}] has invalid left or right values";
            } elseif ($right == $left) {
                $errors[] = "node [{$id}] has identical left and right values";
            } elseif ($parent) {
                if ($parent instanceof Proxy && !$parent->__isInitialized__) {
                    $this->_em->refresh($parent);
                }
                $parentRight = $meta->getReflectionProperty($config['right'])->getValue($parent);
                $parentLeft = $meta->getReflectionProperty($config['left'])->getValue($parent);
                $parentId = $meta->getReflectionProperty($identifier)->getValue($parent);
                if ($left < $parentLeft) {
                    $errors[] = "node [{$id}] left is less than parent`s [{$parentId}] left value";
                } elseif ($right > $parentRight) {
                    $errors[] = "node [{$id}] right is greater than parent`s [{$parentId}] right value";
                }
            } else {
                $dql = "SELECT COUNT(node.{$identifier}) FROM {$config['useObjectClass']} node";
                $dql .= " WHERE node.{$config['left']} < {$left}";
                $dql .= " AND node.{$config['right']} > {$right}";
                if ($root) {
                    $dql .= " AND node.{$config['root']} = {$rootId}";
                }
                $q = $this->_em->createQuery($dql);
                if ($count = intval($q->getSingleScalarResult())) {
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
    private function removeSingle(EntityWrapper $wrapped)
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->_em, $meta->name);

        $pk = $meta->getSingleIdentifierFieldName();
        $nodeId = $wrapped->getIdentifier();

        // prevent from deleting whole branch
        $dql = "UPDATE {$config['useObjectClass']} node";
        $dql .= ' SET node.' . $config['left'] . ' = 0,';
        $dql .= ' node.' . $config['right'] . ' = 0';
        $dql .= ' WHERE node.' . $pk . ' = ' . $nodeId;
        $this->_em->createQuery($dql)->getSingleScalarResult();

        // remove the node from database
        $dql = "DELETE {$config['useObjectClass']} node";
        $dql .= " WHERE node.{$pk} = {$nodeId}";
        $this->_em->createQuery($dql)->getSingleScalarResult();

        // remove from identity map
        $this->_em->getUnitOfWork()->removeFromIdentityMap($wrapped->getObject());
    }

    /**
     * Retrieves the nested array or the html output
     *
     * @throws \Gedmo\Exception\InvalidArgumentException
     * @param object $node - from which node to start reordering the tree
     * @param boolean $direct - true to take only direct children
     * @param bool $html
     * @param array|null $options
     * @return array|string
     */
    public function childrenHierarchy($node = null, $direct = false, $html = false, array $options = null)
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->_em, $meta->name);

        if ($node !== null) {
            if ($node instanceof $meta->name) {
                $wrapped = new EntityWrapper($node, $this->_em);
                if (!$wrapped->hasValidIdentifier()) {
                    throw new InvalidArgumentException("Node is not managed by UnitOfWork");
                }
            }
        }

        // Gets the array of $node results.
        // It must be order by 'root' field
        $nodes = self::childrenQuery($node, $direct, 'root' , 'ASC')->getArrayResult();

        return $this->buildTree($nodes, $html, $options);
    }

    /**
     * Builds the tree
     *
     * @param array $nodes
     * @param bool $html
     * @param array|null $options
     * @return array|string
     */
    private function buildTree(array $nodes, $html = false, array $options = null)
    {
        //process the nested tree into a nested array
        $nestedTree = $this->processTree($nodes);
        
        // If you don't want any html output it will return the nested array
        if (!$html) {
            return $nestedTree;
        }

        //Defines html decorators and opcional options
        if (!empty($options['root'])) {
            $root_open  = $options['root']['open'];
            $root_close = $options['root']['close'];
        } else {
            $root_open  = "<ul> ";
            $root_close = " </ul>";
        }
        if (!empty($options['child'])) {
            $child_open  = $options['child']['open'];
            $child_close = $options['child']['close'];
        } else {
            $child_open  = "<li> ";
            $child_close = " </li>";
        }

        $html_decorator = array(
            'root'  => array('open' => $root_open,  'close' => $root_close),
            'child' => array('open' => $child_open, 'close' => $child_close),
        );

        $html_output = $this->processHtmlTree($nestedTree, $html_decorator, $html_output = null);

        return $html_output;
    }

    /**
     * Creates the nested array
     *
     * @static
     * @param array $nodes
     * @return array
     */
    private static function processTree($nodes)
    {
        // Trees mapped
        $trees = array();
        $l = 0;

        if (count($nodes) > 0) {
            // Node Stack. Used to help building the hierarchy
            $stack = array();

            foreach ($nodes as $child) {
                $item = $child;

                $item['children'] = array();

                // Number of stack items
                $l = count($stack);

                // Check if we're dealing with different levels
                while($l > 0 && $stack[$l - 1]['lvl'] >= $item['lvl']) {
                    array_pop($stack);
                    $l--;
                }

                // Stack is empty (we are inspecting the root)
                if ($l == 0) {
                    // Assigning the root child
                    $i = count($trees);
                    $trees[$i] = $item;
                    $stack[] = & $trees[$i];
                } else {
                    // Add child to parent
                    $i = count($stack[$l - 1]['children']);
                    $stack[$l - 1]['children'][$i] = $item;
                    $stack[] = & $stack[$l - 1]['children'][$i];
                }
            }
        }
        return $trees;
    }

    /**
     * Creates the html output of the nested tree
     *
     * @param $parent_node
     * @param $html_decorator
     * @param $html_output
     * @return string
     */
    private function processHtmlTree($parent_node, $html_decorator, $html_output)
    {
        if (is_array($parent_node)) {
            $html_output .= $html_decorator['root']['open'];
            foreach ($parent_node as $item) {
                 $html_output .= $html_decorator['child']['open'] . $item['title'];
                if (count($item['children']) > 0) {
                    $html_output = $this->processHtmlTree($item['children'], $html_decorator, $html_output);
                }
                $html_output .= $html_decorator['child']['close'];
            }
            $html_output .= $html_decorator['root']['close'];
        }
            
        return $html_output;
    }
}