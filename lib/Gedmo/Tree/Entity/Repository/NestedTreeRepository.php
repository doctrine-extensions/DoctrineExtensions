<?php

namespace Gedmo\Tree\Entity\Repository;

use Gedmo\Tree\AbstractTreeRepository,
    Doctrine\ORM\Query,
    Gedmo\Tree\Strategy,
    Gedmo\Tree\Strategy\ORM\Nested,
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
     * Get all root nodes
     * 
     * @return array
     */
    public function getRoodNodes()
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->_em, $meta->name);
        $qb = $this->_em->createQueryBuilder();
        $qb->select('node')
            ->from($meta->rootEntityName, 'node')
            ->where('node.' . $config['parent'] . " IS NULL")
            ->orderBy('node.' . $config['left'], 'ASC');
        return $qb->getQuery()->getResult(Query::HYDRATE_OBJECT);
    }
    
    /**
     * Get the Tree path of Nodes by given $node
     * 
     * @param object $node
     * @return array - list of Nodes in path
     */
    public function getPath($node)
    {
        $result = array();
        $meta = $this->getClassMetadata();

        if ($node instanceof $meta->rootEntityName) {
            $config = $this->listener->getConfiguration($this->_em, $meta->name);
            $left = $meta->getReflectionProperty($config['left'])->getValue($node);
            $right = $meta->getReflectionProperty($config['right'])->getValue($node);
            if ($left && $right) {
                $qb = $this->_em->createQueryBuilder();
                $qb->select('node')
                    ->from($meta->rootEntityName, 'node')
                    ->where('node.' . $config['left'] . " <= :left")
                    ->andWhere('node.' . $config['right'] . " >= :right")
                    ->orderBy('node.' . $config['left'], 'ASC');
                if (isset($config['root'])) {
                    $rootId = $meta->getReflectionProperty($config['root'])->getValue($node);
                    $qb->andWhere("node.{$config['root']} = {$rootId}");
                }
                $q = $qb->getQuery();
                $result = $q->execute(
                    compact('left', 'right'),
                    Query::HYDRATE_OBJECT
                );
            }
        } else {
            throw \Gedmo\Exception\InvalidArgumentException("Node is not related to this repository");
        }
        return $result;
    }
    
    /**
     * Counts the children of given TreeNode
     * 
     * @param object $node - if null counts all records in tree
     * @param boolean $direct - true to count only direct children
     * @return integer
     */ 
    public function childCount($node = null, $direct = false)
    {
        $count = 0;
        $meta = $this->getClassMetadata();
        $nodeId = $meta->getSingleIdentifierFieldName();
        $config = $this->listener->getConfiguration($this->_em, $meta->name);
        if (null !== $node) {
            if ($node instanceof $meta->rootEntityName) {
                if ($direct) {
                    $id = $meta->getReflectionProperty($nodeId)->getValue($node);
                    $qb = $this->_em->createQueryBuilder();
                    $qb->select('COUNT(node.' . $nodeId . ')')
                        ->from($meta->rootEntityName, 'node')
                        ->where('node.' . $config['parent'] . ' = ' . $id);
                        
                    $q = $qb->getQuery();
                    $count = intval($q->getSingleScalarResult());
                } else {
                    $left = $meta->getReflectionProperty($config['left'])->getValue($node);
                    $right = $meta->getReflectionProperty($config['right'])->getValue($node);
                    if ($left && $right) {
                        $count = ($right - $left - 1) / 2;
                    }
                }
            } else {
                throw \Gedmo\Exception\InvalidArgumentException("Node is not related to this repository");
            }
        } else {
            $dql = "SELECT COUNT(node.{$nodeId}) FROM " . $meta->rootEntityName . " node";
            if ($direct) {
                $dql .= ' WHERE node.' . $config['parent'] . ' IS NULL';
            }
            $q = $this->_em->createQuery($dql);
            $count = intval($q->getSingleScalarResult());
        }
        return $count;
    }
    
    /**
     * Get list of children followed by given $node
     * 
     * @param object $node - if null, all tree nodes will be taken
     * @param boolean $direct - true to take only direct children
     * @param string $sortByField - field name to sort by
     * @param string $direction - sort direction : "ASC" or "DESC"
     * @throws InvalidArgumentException - if input is not valid
     * @return array - list of given $node children, null on failure
     */
    public function children($node = null, $direct = false, $sortByField = null, $direction = 'ASC')
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->_em, $meta->name);
             
        $qb = $this->_em->createQueryBuilder();
        $qb->select('node')
            ->from($meta->rootEntityName, 'node');
        if ($node !== null) {
            if ($node instanceof $meta->rootEntityName) {
                if ($direct) {
                    $nodeId = $meta->getSingleIdentifierFieldName();
                    $id = $meta->getReflectionProperty($nodeId)->getValue($node);
                    $qb->where('node.' . $config['parent'] . ' = ' . $id);
                } else {
                    $left = $meta->getReflectionProperty($config['left'])->getValue($node);
                    $right = $meta->getReflectionProperty($config['right'])->getValue($node);
                    if ($left && $right) {
                        $qb->where('node.' . $config['right'] . " < {$right}")
                            ->andWhere('node.' . $config['left'] . " > {$left}");
                    }
                }
            } else {
                throw \Gedmo\Exception\InvalidArgumentException("Node is not related to this repository");
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
                throw new \Gedmo\Exception\InvalidArgumentException("Invalid sort options specified: field - {$sortByField}, direction - {$direction}");
            }
        }
        $q = $qb->getQuery();
        return $q->getResult(Query::HYDRATE_OBJECT);
    }
    
    /**
     * Get list of leaf nodes of the tree
     *
     * @param object $root - root node in case of root tree is required
     * @param string $sortByField - field name to sort by
     * @param string $direction - sort direction : "ASC" or "DESC"
     * @throws InvalidArgumentException - if input is not valid
     * @return array
     */
    public function getLeafs($root = null, $sortByField = null, $direction = 'ASC')
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->_em, $meta->name);

        if (isset($config['root']) && is_null($root)) {
            throw \Gedmo\Exception\InvalidArgumentException("If tree has root, getLiefs method requires root node");
        }
        
        $qb = $this->_em->createQueryBuilder();
        $qb->select('node')
            ->from($meta->rootEntityName, 'node')
            ->where('node.' . $config['right'] . ' = 1 + node.' . $config['left']);
        if (isset($config['root'])) {
            if ($root instanceof $meta->rootEntityName) {
                $rootId = $meta->getReflectionProperty($config['root'])->getValue($root);
                $qb->andWhere("node.{$config['root']} = {$rootId}");
            } else {
                throw \Gedmo\Exception\InvalidArgumentException("Node is not related to this repository");
            }
        }
        if (!$sortByField) {
            $qb->orderBy('node.' . $config['left'], 'ASC');
        } else {
            if ($meta->hasField($sortByField) && in_array(strtolower($direction), array('asc', 'desc'))) {
                $qb->orderBy('node.' . $sortByField, $direction);
            } else {
                throw new \Gedmo\Exception\InvalidArgumentException("Invalid sort options specified: field - {$sortByField}, direction - {$direction}");
            }
        }
        $q = $qb->getQuery();
        return $q->getResult(Query::HYDRATE_OBJECT);
    }
    
    /**
     * Find the next siblings of the given $node
     * 
     * @param object $node
     * @param bool $includeSelf - include the node itself
     * @throws \Gedmo\Exception\InvalidArgumentException - if input is invalid
     * @return array
     */
    public function getNextSiblings($node, $includeSelf = false)
    {
        $result = array();
        $meta = $this->getClassMetadata();

        if ($node instanceof $meta->rootEntityName) {
            $config = $this->listener->getConfiguration($this->_em, $meta->name);
            $parent = $meta->getReflectionProperty($config['parent'])->getValue($node);
            if (!$parent) {
                throw new \Gedmo\Exception\InvalidArgumentException("Cannot get siblings from tree root node");
            }
            $identifierField = $meta->getSingleIdentifierFieldName();
            if ($parent instanceof Proxy) {
                $this->_em->refresh($parent);
            }
            $parentId = $meta->getReflectionProperty($identifierField)->getValue($parent);
            
            $left = $meta->getReflectionProperty($config['left'])->getValue($node);
            $sign = $includeSelf ? '>=' : '>';
            
            $dql = "SELECT node FROM {$meta->rootEntityName} node";
            $dql .= " WHERE node.{$config['parent']} = {$parentId}";
            $dql .= " AND node.{$config['left']} {$sign} {$left}";
            $dql .= " ORDER BY node.{$config['left']} ASC";
            $result = $this->_em->createQuery($dql)->getResult(Query::HYDRATE_OBJECT);
        } else {
            throw \Gedmo\Exception\InvalidArgumentException("Node is not related to this repository");
        }
        return $result;
    }
    
    /**
     * Find the previous siblings of the given $node
     * 
     * @param object $node
     * @param bool $includeSelf - include the node itself
     * @throws \Gedmo\Exception\InvalidArgumentException - if input is invalid
     * @return array
     */
    public function getPrevSiblings($node, $includeSelf = false)
    {
        $result = array();
        $meta = $this->getClassMetadata();
        
        if ($node instanceof $meta->rootEntityName) {
            $config = $this->listener->getConfiguration($this->_em, $meta->name);
            $parent = $meta->getReflectionProperty($config['parent'])->getValue($node);
            if (!$parent) {
                throw new \Gedmo\Exception\InvalidArgumentException("Cannot get siblings from tree root node");
            }
            $identifierField = $meta->getSingleIdentifierFieldName();
            if ($parent instanceof Proxy) {
                $this->_em->refresh($parent);
            }
            $parentId = $meta->getReflectionProperty($identifierField)->getValue($parent);

            $left = $meta->getReflectionProperty($config['left'])->getValue($node);
            $sign = $includeSelf ? '<=' : '<';
            
            $dql = "SELECT node FROM {$meta->rootEntityName} node";
            $dql .= " WHERE node.{$config['parent']} = {$parentId}";
            $dql .= " AND node.{$config['left']} {$sign} {$left}";
            $dql .= " ORDER BY node.{$config['left']} ASC";
            $result = $this->_em->createQuery($dql)->getResult(Query::HYDRATE_OBJECT);
        } else {
            throw \Gedmo\Exception\InvalidArgumentException("Node is not related to this repository");
        }
        return $result;
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
        if ($node instanceof $meta->rootEntityName) {
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
            throw \Gedmo\Exception\InvalidArgumentException("Node is not related to this repository");
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
        if ($node instanceof $meta->rootEntityName) {
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
            throw \Gedmo\Exception\InvalidArgumentException("Node is not related to this repository");
        }
        return $result;
    }
    
    /**
     * Removes given $node from the tree and reparents its descendants
     * 
     * @param object $node
     * @param bool $autoFlush - flush after removing
     * @throws RuntimeException - if something fails in transaction
     * @return void
     */
    public function removeFromTree($node, $autoFlush = true)
    {
        $meta = $this->getClassMetadata();
        if ($node instanceof $meta->rootEntityName) {
            $config = $this->listener->getConfiguration($this->_em, $meta->name);
            $right = $meta->getReflectionProperty($config['right'])->getValue($node);
            $left = $meta->getReflectionProperty($config['left'])->getValue($node);
                
            if ($right == $left + 1) {
                $this->_em->remove($node);
                $autoFlush && $this->_em->flush();
                return;
            }
            // process updates in transaction
            $this->_em->getConnection()->beginTransaction();
            try {
                $parent = $meta->getReflectionProperty($config['parent'])->getValue($node);
                if ($parent instanceof Proxy) {
                    $this->_em->refresh($parent);
                }
                $pk = $meta->getSingleIdentifierFieldName();
                $parentId = $meta->getReflectionProperty($pk)->getValue($parent);
                $nodeId = $meta->getReflectionProperty($pk)->getValue($node);
                
                $dql = "UPDATE {$meta->rootEntityName} node";
                $dql .= ' SET node.' . $config['parent'] . ' = ' . $parentId;
                $dql .= ' WHERE node.' . $config['parent'] . ' = ' . $nodeId;
                $rootId = isset($config['root']) ? $meta->getReflectionProperty($config['root'])->getValue($node) : null;
                if (isset($config['root'])) {
                    $dql .= ' AND node.' . $config['root'] . ' = ' . $rootId;
                }
                $q = $this->_em->createQuery($dql);
                $q->getSingleScalarResult();
                
                $this->listener
                    ->getStrategy($this->_em, $meta->name)
                    ->shiftRangeRL($this->_em, $meta->rootEntityName, $left, $right, -1, $rootId, $rootId, - 1);
                    
                $this->listener
                    ->getStrategy($this->_em, $meta->name)
                    ->shiftRL($this->_em, $meta->rootEntityName, $right, -2, $rootId);
                
                $dql = "UPDATE {$meta->rootEntityName} node";
                $dql .= ' SET node.' . $config['parent'] . ' = NULL,';
                $dql .= ' node.' . $config['left'] . ' = 0,';
                $dql .= ' node.' . $config['right'] . ' = 0';
                $dql .= ' WHERE node.' . $pk . ' = ' . $nodeId;
                $q = $this->_em->createQuery($dql);
                $q->getSingleScalarResult();
                $this->_em->getConnection()->commit();
            } catch (\Exception $e) {
                $this->_em->close();
                $this->_em->getConnection()->rollback();
                throw new \Gedmo\Exception\RuntimeException('Transaction failed', null, $e);
            }
            $this->_em->refresh($node);
            $this->_em->remove($node);
            $autoFlush && $this->_em->flush();
        } else {
            throw \Gedmo\Exception\InvalidArgumentException("Node is not related to this repository");
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
        if ($node instanceof $meta->rootEntityName) {
            $config = $this->listener->getConfiguration($this->_em, $meta->name);
            if ($verify && is_array($this->verify())) {
                return false;
            }
                   
            $nodes = $this->children($node, true, $sortByField, $direction);
            foreach ($nodes as $node) {
                // this is overhead but had to be refreshed
                if ($node instanceof Proxy) {
                    $this->_em->refresh($node);
                }
                $right = $meta->getReflectionProperty($config['right'])->getValue($node);
                $left = $meta->getReflectionProperty($config['left'])->getValue($node);
                $this->moveDown($node, true);
                if ($left != ($right - 1)) {
                    $this->reorder($node, $sortByField, $direction, false);
                }
            }
        } else {
            throw \Gedmo\Exception\InvalidArgumentException("Node is not related to this repository");
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
            $trees = $this->getRoodNodes();
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
        
        $dql = "SELECT MIN(node.{$config['left']}) FROM {$meta->rootEntityName} node";
        if ($root) {
            $dql .= " WHERE node.{$config['root']} = {$rootId}";
        }
        $min = intval($this->_em->createQuery($dql)->getSingleScalarResult());
        $edge = $this->listener->getStrategy($this->_em, $meta->name)->max($this->_em, $meta->name, $rootId);
        // check duplicate right and left values
        for ($i = $min; $i <= $edge; $i++) {
            $dql = "SELECT COUNT(node.{$identifier}) FROM {$meta->rootEntityName} node";
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
        $dql = "SELECT node FROM {$meta->rootEntityName} node";
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
        
        $dql = "SELECT node FROM {$meta->rootEntityName} node";
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
        
        $dql = "SELECT node FROM {$meta->rootEntityName} node";
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
                $parent instanceof Proxy && $this->_em->refresh($parent);
                $parentRight = $meta->getReflectionProperty($config['right'])->getValue($parent);
                $parentLeft = $meta->getReflectionProperty($config['left'])->getValue($parent);
                $parentId = $meta->getReflectionProperty($identifier)->getValue($parent);
                if ($left < $parentLeft) {
                    $errors[] = "node [{$id}] left is less than parent`s [{$parentId}] left value";
                } elseif ($right > $parentRight) {
                    $errors[] = "node [{$id}] right is greater than parent`s [{$parentId}] right value";
                }
            } else {
                $dql = "SELECT COUNT(node.{$identifier}) FROM {$meta->rootEntityName} node";
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
}
