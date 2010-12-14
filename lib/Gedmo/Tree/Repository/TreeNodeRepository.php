<?php

namespace Gedmo\Tree\Repository;

use Doctrine\ORM\EntityRepository,
    Doctrine\ORM\Query,
    Gedmo\Tree\Node;

/**
 * The TreeNodeRepository has some useful functions
 * to interact with tree.
 * 
 * Some Tree logic is copied from -
 * CakePHP: Rapid Development Framework (http://cakephp.org)
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree.Repository
 * @subpackage TreeNodeRepository
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TreeNodeRepository extends EntityRepository
{   
    /**
     * List of cached entity configurations
     *  
     * @var array
     */
    protected $_configurations = array();
    
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
        $config = $this->getConfiguration();
            
        $left = $meta->getReflectionProperty($config['left'])->getValue($node);
        $right = $meta->getReflectionProperty($config['right'])->getValue($node);
        if ($left && $right) {
            $qb = $this->_em->createQueryBuilder();
            $qb->select('node')
                ->from($this->_entityName, 'node')
                ->where('node.' . $config['left'] . " <= :left")
                ->andWhere('node.' . $config['right'] . " >= :right")
                ->orderBy('node.' . $config['left'], 'ASC');
            $q = $qb->getQuery();
            $result = $q->execute(
                compact('left', 'right'),
                Query::HYDRATE_OBJECT
            );
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
        $config = $this->getConfiguration();
        if (null !== $node) {
            if ($direct) {
                $id = $meta->getReflectionProperty($nodeId)->getValue($node);
                $qb = $this->_em->createQueryBuilder();
                $qb->select('COUNT(node.' . $nodeId . ')')
                    ->from($this->_entityName, 'node')
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
            $dql = "SELECT COUNT(node.{$nodeId}) FROM {$this->_entityName} node";
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
     * @return array - list of given $node children, null on failure
     */
    public function children($node = null, $direct = false, $sortByField = null, $direction = 'ASC')
    {
        $meta = $this->getClassMetadata();
        $config = $this->getConfiguration();
             
        $qb = $this->_em->createQueryBuilder();
        $qb->select('node')
            ->from($this->_entityName, 'node');
        if ($node !== null) {
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
                throw new \RuntimeException("Invalid sort options specified: field - {$sortByField}, direction - {$direction}");
            }
        }
        $q = $qb->getQuery();
        $q->useResultCache(false);
        $q->useQueryCache(false);
        return $q->getResult(Query::HYDRATE_OBJECT);
    }

    /**
     * Get list of leaf nodes of the tree
     *
     * @param string $sortByField - field name to sort by
     * @param string $direction - sort direction : "ASC" or "DESC"
     * @return array - list of given $node children, null on failure
     */
    public function getLeafs($sortByField = null, $direction = 'ASC')
    {
        $meta = $this->getClassMetadata();
        $config = $this->getConfiguration();

        $qb = $this->_em->createQueryBuilder();
        $qb->select('node')
            ->from($this->_entityName, 'node')
            ->where('node.' . $config['right'] . ' = 1 + node.' . $config['left']);
        if (!$sortByField) {
            $qb->orderBy('node.' . $config['left'], 'ASC');
        } else {
            if ($meta->hasField($sortByField) && in_array(strtolower($direction), array('asc', 'desc'))) {
                $qb->orderBy('node.' . $sortByField, $direction);
            } else {
                throw new \RuntimeException("Invalid sort options specified: field - {$sortByField}, direction - {$direction}");
            }
        }
        $q = $qb->getQuery();
        return $q->getResult(Query::HYDRATE_OBJECT);
    }
    
    /**
     * Move the node down in the same level
     * 
     * @param object $node
     * @param mixed $number
     *         integer - number of positions to shift
     *         boolean - true shift till last position
     * @throws Exception if something fails in transaction
     * @return boolean - true if shifted
     */
    public function moveDown($node, $number = 1)
    {
        $meta = $this->getClassMetadata();
        $config = $this->getConfiguration();
        if (!$number) {
            return false;
        }
        
        $parent = $meta->getReflectionProperty($config['parent'])->getValue($node);
        $right = $meta->getReflectionProperty($config['right'])->getValue($node);
        
        if ($parent) {
            $this->_em->refresh($parent);
            $parentRight = $meta->getReflectionProperty($config['right'])->getValue($parent);
            if (($right + 1) == $parentRight) {
                return false;
            }
        }
        $dql = "SELECT node FROM {$this->_entityName} node";
        $dql .= ' WHERE node.' . $config['left'] . ' = ' . ($right + 1);
        $q = $this->_em->createQuery($dql);
        $q->setMaxResults(1);
        $result = $q->getResult(Query::HYDRATE_OBJECT);
        $nextSiblingNode = count($result) ? array_shift($result) : null;
        
        if (!$nextSiblingNode) {
            return false;
        }
        
        // this one is very important because if em is not cleared
        // it loads node from memory without refresh
        $this->_em->refresh($nextSiblingNode);
        
        $left = $meta->getReflectionProperty($config['left'])->getValue($node);
        $nextLeft = $meta->getReflectionProperty($config['left'])->getValue($nextSiblingNode);
        $nextRight = $meta->getReflectionProperty($config['right'])->getValue($nextSiblingNode);
        $edge = $this->_getTreeEdge($config);
        // process updates in transaction
        $this->_em->getConnection()->beginTransaction();
        try {            
            $this->_sync($config, $edge - $left + 1, '+', 'BETWEEN ' . $left . ' AND ' . $right);
            $this->_sync($config, $nextLeft - $left, '-', 'BETWEEN ' . $nextLeft . ' AND ' . $nextRight);
            $this->_sync($config, $edge - $left - ($nextRight - $nextLeft), '-', ' > ' . $edge);
            $this->_em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->_em->close();
            $this->_em->getConnection()->rollback();
            throw $e;
        }
        if (is_int($number)) {
            $number--;
        }
        if ($number) {
            $this->_em->refresh($node);
            $this->moveDown($node, $number);
        }
        return true;
    }
    
    /**
     * Move the node up in the same level
     * 
     * @param object $node
     * @param mixed $number
     *         integer - number of positions to shift
     *         boolean - true shift till first position
     * @throws Exception if something fails in transaction
     * @return boolean - true if shifted
     */
    public function moveUp($node, $number = 1)
    {
        $meta = $this->getClassMetadata();
        $config = $this->getConfiguration();
        if (!$number) {
            return false;
        }
        
        $parent = $meta->getReflectionProperty($config['parent'])->getValue($node);            
        $left = $meta->getReflectionProperty($config['left'])->getValue($node);
        if ($parent) {
            $this->_em->refresh($parent);
            $parentLeft = $meta->getReflectionProperty($config['left'])->getValue($parent);
            if (($left - 1) == $parentLeft) {
                return false;
            }
        }
        
        $dql = "SELECT node FROM {$this->_entityName} node";
        $dql .= ' WHERE node.' . $config['right'] . ' = ' . ($left - 1);
        $q = $this->_em->createQuery($dql);
        $q->setMaxResults(1);
        $result = $q->getResult(Query::HYDRATE_OBJECT);
        $previousSiblingNode = count($result) ? array_shift($result) : null;
        
        if (!$previousSiblingNode) {
            return false;
        }
        // this one is very important because if em is not cleared
        // it loads node from memory without refresh
        $this->_em->refresh($previousSiblingNode);
        
        $right = $meta->getReflectionProperty($config['right'])->getValue($node);
        $previousLeft = $meta->getReflectionProperty($config['left'])->getValue($previousSiblingNode);
        $previousRight = $meta->getReflectionProperty($config['right'])->getValue($previousSiblingNode);
        $edge = $this->_getTreeEdge($config);
        // process updates in transaction
        $this->_em->getConnection()->beginTransaction();
        try {
            $this->_sync($config, $edge - $previousLeft +1, '+', 'BETWEEN ' . $previousLeft . ' AND ' . $previousRight);
            $this->_sync($config, $left - $previousLeft, '-', 'BETWEEN ' .$left . ' AND ' . $right);
            $this->_sync($config, $edge - $previousLeft - ($right - $left), '-', '> ' . $edge);
            $this->_em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->_em->close();
            $this->_em->getConnection()->rollback();
            throw $e;
        }
        if (is_int($number)) {
            $number--;
        }
        if ($number) {
            $this->_em->refresh($node);
            $this->moveUp($node, $number);
        }
        return true;
    }
    
    /**
     * Reorders the sibling nodes and child nodes by given $node,
     * according to the $sortByField and $direction specified
     * 
     * @param object $node - null to reorder all tree
     * @param string $sortByField - field name to sort by
     * @param string $direction - sort direction : "ASC" or "DESC"
     * @param boolean $verify - true to verify tree first
     * @return boolean - true on success
     */
    public function reorder($node = null, $sortByField = null, $direction = 'ASC', $verify = true)
    {
        $meta = $this->getClassMetadata();
        $config = $this->getConfiguration();
        if ($verify && is_array($this->verify())) {
            return false;
        }
               
        $nodes = $this->children($node, true, $sortByField, $direction);
        foreach ($nodes as $node) {
            // this is overhead but had to be refreshed
            $this->_em->refresh($node);
            $right = $meta->getReflectionProperty($config['right'])->getValue($node);
            $left = $meta->getReflectionProperty($config['left'])->getValue($node);
            $this->moveDown($node, true);
            if ($left != ($right - 1)) {
                $this->reorder($node, $sortByField, $direction, false);
            }
        }
        return true;
    }
    
    /**
     * Removes given $node from the tree and reparents its descendants
     * 
     * @param Node $node
     * @throws Exception if something fails in transaction
     * @return void
     */
    public function removeFromTree(Node $node)
    {
        $meta = $this->getClassMetadata();
        $config = $this->getConfiguration();
        
        $right = $meta->getReflectionProperty($config['right'])->getValue($node);
        $left = $meta->getReflectionProperty($config['left'])->getValue($node);
        $parent = $meta->getReflectionProperty($config['parent'])->getValue($node);
            
        if ($right == $left + 1) {
            $this->_em->remove($node);
            $this->_em->flush();
            return;
        }
        // process updates in transaction
        $this->_em->getConnection()->beginTransaction();
        try {
            $this->_em->refresh($parent);
            $pk = $meta->getSingleIdentifierFieldName();
            $parentId = $meta->getReflectionProperty($pk)->getValue($parent);
            $nodeId = $meta->getReflectionProperty($pk)->getValue($node);
            
            $dql = "UPDATE {$this->_entityName} node";
            $dql .= ' SET node.' . $config['parent'] . ' = ' . $parentId;
            $dql .= ' WHERE node.' . $config['parent'] . ' = ' . $nodeId;
            $q = $this->_em->createQuery($dql);
            $q->getSingleScalarResult();
            
            $this->_sync($config, 1, '-', 'BETWEEN ' . ($left + 1) . ' AND ' . ($right - 1));
            $this->_sync($config, 2, '-', '> ' . $right);
            
            $dql = "UPDATE {$this->_entityName} node";
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
            throw $e;
        }
        $this->_em->refresh($node);
        $this->_em->remove($node);
        $this->_em->flush();
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
        $config = $this->getConfiguration();
        $identifier = $meta->getSingleIdentifierFieldName();
        $leftField = $config['left'];
        $rightField = $config['right'];
        $parentField = $config['parent'];
        
        $q = $this->_em->createQuery("SELECT MIN(node.{$leftField}) FROM {$this->_entityName} node");
        
        $min = intval($q->getSingleScalarResult());
        $edge = $this->_getTreeEdge($config);
        for ($i = $min; $i <= $edge; $i++) {
            $dql = "SELECT COUNT(node.{$identifier}) FROM {$this->_entityName} node";
            $dql .= " WHERE (node.{$leftField} = {$i} OR node.{$rightField} = {$i})";
            $q = $this->_em->createQuery($dql);
            $count = intval($q->getSingleScalarResult());
            if ($count != 1) {
                if ($count == 0) {
                    $errors[] = "index [{$i}], missing";
                } else {
                    $errors[] = "index [{$i}], duplicate";
                }
            }
        }
        
        // check for missing parents
        $dql = "SELECT c FROM {$this->_entityName} c";
        $dql .= " LEFT JOIN c.{$parentField} p";
        $dql .= " WHERE c.{$parentField} IS NOT NULL";
        $dql .= " AND p.{$identifier} IS NULL";
        $q = $this->_em->createQuery($dql);
        $nodes = $q->getArrayResult();
        if (count($nodes)) {
            foreach ($nodes as $node) {
                $errors[] = "node [{$node[$identifier]}] has missing parent";
            }
            return $errors; // loading broken relation can cause infinite loop
        }
        
        $dql = "SELECT node FROM {$this->_entityName} node";
        $dql .= " WHERE node.{$rightField} < node.{$leftField}";
        $q = $this->_em->createQuery($dql);
        $q->setMaxResults(1);
        $result = $q->getResult(Query::HYDRATE_OBJECT);
        $node = count($result) ? array_shift($result) : null; 
        
        if ($node) {
            $id = $meta->getReflectionProperty($identifier)->getValue($node);
            $errors[] = "node [{$id}], left is greater than right";
        }
        
        foreach ($this->findAll() as $node) {
            $right = $meta->getReflectionProperty($rightField)->getValue($node);
            $left = $meta->getReflectionProperty($leftField)->getValue($node);
            $id = $meta->getReflectionProperty($identifier)->getValue($node);
            $parent = $meta->getReflectionProperty($parentField)->getValue($node);
            if (!$right || !$left) {
                $errors[] = "node [{$id}] has invalid left or right values";
            } elseif ($right == $left) {
                $errors[] = "node [{$id}] has identical left and right values";
            } elseif ($parent) {
                $this->_em->refresh($parent);
                $parentRight = $meta->getReflectionProperty($rightField)->getValue($parent);
                $parentLeft = $meta->getReflectionProperty($leftField)->getValue($parent);
                $parentId = $meta->getReflectionProperty($identifier)->getValue($parent);
                if ($left < $parentLeft) {
                    $errors[] = "node [{$id}] left is less than parent`s [{$parentId}] left value";
                } elseif ($right > $parentRight) {
                    $errors[] = "node [{$id}] right is greater than parent`s [{$parentId}] right value";
                }
            } else {
                $dql = "SELECT COUNT(node.{$identifier}) FROM {$this->_entityName} node";
                $dql .= " WHERE node.{$leftField} < {$left}";
                $dql .= " AND node.{$rightField} > {$right}";
                $q = $this->_em->createQuery($dql);
                if ($count = intval($q->getSingleScalarResult())) {
                    $errors[] = "node [{$id}] parent field is blank, but it has a parent";
                }
            }
        }
        return $errors ?: true;
    }
    
    /**
     * Tries to recover the tree
     * 
     * @throws Exception if something fails in transaction
     * @return void
     */
    public function recover()
    {
        if ($this->verify() === true) {
            return;
        }
        
        $meta = $this->getClassMetadata();
        $config = $this->getConfiguration();
        
        $identifier = $meta->getSingleIdentifierFieldName();
        $leftField = $config['left'];
        $rightField = $config['right'];
        $parentField = $config['parent'];
        
        $count = 1;
        $dql = "SELECT node.{$identifier} FROM {$this->_entityName} node";
        $dql .= " ORDER BY node.{$leftField} ASC";
        $q = $this->_em->createQuery($dql);
        $nodes = $q->getArrayResult();
        // process updates in transaction
        $this->_em->getConnection()->beginTransaction();
        try {
            foreach ($nodes as $node) {
                $left = $count++;
                $right = $count++;
                $dql = "UPDATE {$this->_entityName} node";
                $dql .= " SET node.{$leftField} = {$left},";
                $dql .= " node.{$rightField} = {$right}";
                $dql .= " WHERE node.{$identifier} = {$node[$identifier]}";
                $q = $this->_em->createQuery($dql);
                $q->getSingleScalarResult();
            }
            foreach ($nodes as $node) {
                $node = $this->_em->getReference($this->_entityName, $node[$identifier]);
                $this->_em->refresh($node);
                $parent = $meta->getReflectionProperty($parentField)->getValue($node);
                $this->_adjustNodeWithParent($config, $parent, $node);
            }
            $this->_em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->_em->close();
            $this->_em->getConnection()->rollback();
            throw $e;
        }
    }
    
    /**
     * Generally loads configuration from cache
     * 
     * @throws RuntimeException if no configuration for class found
     * @return array
     */
    public function getConfiguration() {
        $config = array();
        if (isset($this->_configurations[$this->_entityName])) {
            $config = $this->_configurations[$this->_entityName];
        } else {
            $cacheDriver = $this->_em->getMetadataFactory()->getCacheDriver();
            $cacheId = \Gedmo\Mapping\ExtensionMetadataFactory::getCacheId(
                $this->_entityName, 
                'Gedmo\Tree'
            );
            if (($cached = $cacheDriver->fetch($cacheId)) !== false) {
                $this->_configurations[$this->_entityName] = $cached;
                $config = $cached;
            }
        }
        if (!$config) {
            throw new \RuntimeException("TreeNodeRepository: this repository cannot be used on {$this->_entityName} without Tree metadata");
        }
        return $config;
    }
    
    /**
     * Get the edge of tree
     *
     * @param array $config
     * @return integer
     */
    protected function _getTreeEdge($config)
    {
        $q = $this->_em->createQuery("SELECT MAX(node.{$config['right']}) FROM {$this->_entityName} node");
        $q->useResultCache(false);
        $q->useQueryCache(false);
        $right = $q->getSingleScalarResult();
        return intval($right);
    }
    
    /**
     * Synchronize the tree with given conditions
     * 
     * @param array $config
     * @param integer $shift
     * @param string $dir
     * @param string $conditions
     * @param string $field
     * @return void
     */
    protected function _sync($config, $shift, $dir, $conditions, $field = 'both')
    {
        if ($field == 'both') {
            $this->_sync($config, $shift, $dir, $conditions, $config['left']);
            $field = $config['right'];
        }
        
        $dql = "UPDATE {$this->_entityName} node";
        $dql .= " SET node.{$field} = node.{$field} {$dir} {$shift}";
        $dql .= " WHERE node.{$field} {$conditions}";
        $q = $this->_em->createQuery($dql);
        return $q->getSingleScalarResult();
    }
    
    /**
     * Synchronize tree according to Node`s parent Node
     * 
     * @param array $config
     * @param Node $parent
     * @param Node $node
     * @return void
     */
    protected function _adjustNodeWithParent($config, $parent, Node $node)
    {
        $edge = $this->_getTreeEdge($config);
        $meta = $this->getClassMetadata();
        $leftField = $config['left'];
        $rightField = $config['right'];
        $parentField = $config['parent'];
        
        $leftValue = $meta->getReflectionProperty($leftField)->getValue($node);
        $rightValue = $meta->getReflectionProperty($rightField)->getValue($node);
        if ($parent === null) {
            $this->_sync($config, $edge - $leftValue + 1, '+', 'BETWEEN ' . $leftValue . ' AND ' . $rightValue);
            $this->_sync($config, $rightValue - $leftValue + 1, '-', '> ' . $leftValue);
        } else {
            // need to refresh the parent to get up to date left and right
            $this->_em->refresh($parent);
            $parentLeftValue = $meta->getReflectionProperty($leftField)->getValue($parent);
            $parentRightValue = $meta->getReflectionProperty($rightField)->getValue($parent);
            if ($leftValue < $parentLeftValue && $parentRightValue < $rightValue) {
                return;
            }
            if (empty($leftValue) && empty($rightValue)) {
                $this->_sync($config, 2, '+', '>= ' . $parentRightValue);
                // cannot schedule this update if other Nodes pending
                $qb = $this->_em->createQueryBuilder();
                $qb->update($this->_entityName, 'node')
                    ->set('node.' . $leftField, $parentRightValue)
                    ->set('node.' . $rightField, $parentRightValue + 1);
                $entityIdentifiers = $meta->getIdentifierValues($node);
                foreach ($entityIdentifiers as $field => $value) {
                    if (strlen($value)) {
                        $qb->where('node.' . $field . ' = ' . $value);
                    }
                }
                $q = $qb->getQuery();
                $q->getSingleScalarResult();
            } else {
                $this->_sync($config, $edge - $leftValue + 1, '+', 'BETWEEN ' . $leftValue . ' AND ' . $rightValue);
                $diff = $rightValue - $leftValue + 1;
                
                if ($leftValue > $parentLeftValue) {
                    if ($rightValue < $parentRightValue) {
                        $this->_sync($config, $diff, '-', 'BETWEEN ' . $rightValue . ' AND ' . ($parentRightValue - 1));
                        $this->_sync($config, $edge - $parentRightValue + $diff + 1, '-', '> ' . $edge);
                    } else {
                        $this->_sync($config, $diff, '+', 'BETWEEN ' . $parentRightValue . ' AND ' . $rightValue);
                        $this->_sync($config, $edge - $parentRightValue + 1, '-', '> ' . $edge);
                    }
                } else {
                    $this->_sync($config, $diff, '-', 'BETWEEN ' . $rightValue . ' AND ' . ($parentRightValue - 1));
                    $this->_sync($config, $edge - $parentRightValue + $diff + 1, '-', '> ' . $edge);
                }
            }
        }
    }
}
