<?php

namespace Gedmo\Tree\Entity\Repository\Adapter;

use Gedmo\Tree\RepositoryAdapterInterface,
    Doctrine\ORM\Query,
    Gedmo\Tree\AbstractTreeListener,
    Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\ORM\EntityManager;

/**
 * This adapter makes tree repository compatible
 * to nested set strategy on tree.
 * 
 * Some Tree logic is copied from -
 * CakePHP: Rapid Development Framework (http://cakephp.org)
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree.Entity.Repository.Adapter
 * @subpackage Nested
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Nested implements RepositoryAdapterInterface
{
    /**
     * Tree listener on event manager
     * 
     * @var AbstractTreeListener
     */
    private $listener = null;
    
    /**
     * The metadata for this adapter
     * 
     * @var ClassMetadata
     */
    private $meta = null;
    
    /**
     * Entity manager
     * 
     * @var EntityManager
     */
    private $em = null;
    
    /**
     * Initialize the nested set adapter
     * 
     * @param AbstractTreeListener $listener
     * @param ClassMetadata $meta
     * @param EntityManager $em
     */
    public function __construct(AbstractTreeListener $listener, ClassMetadata $meta, EntityManager $em)
    {
        $this->listener = $listener;
        $this->meta = $meta;
        $this->em = $em;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getNodePathInTree($node)
    {
        $result = array();
        $config = $this->listener->getConfiguration($this->em, $this->meta->name);
            
        $left = $this->meta->getReflectionProperty($config['left'])->getValue($node);
        $right = $this->meta->getReflectionProperty($config['right'])->getValue($node);
        if ($left && $right) {
            $qb = $this->em->createQueryBuilder();
            $qb->select('node')
                ->from($this->meta->rootEntityName, 'node')
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
     * {@inheritdoc}
     */
    public function countChildren($node, $direct)
    {
        $count = 0;
        $nodeId = $this->meta->getSingleIdentifierFieldName();
        $config = $this->listener->getConfiguration($this->em, $this->meta->name);
        if (null !== $node) {
            if ($direct) {
                $id = $this->meta->getReflectionProperty($nodeId)->getValue($node);
                $qb = $this->em->createQueryBuilder();
                $qb->select('COUNT(node.' . $nodeId . ')')
                    ->from($this->meta->rootEntityName, 'node')
                    ->where('node.' . $config['parent'] . ' = ' . $id);
                    
                $q = $qb->getQuery();
                $count = intval($q->getSingleScalarResult());
            } else {
                $left = $this->meta->getReflectionProperty($config['left'])->getValue($node);
                $right = $this->meta->getReflectionProperty($config['right'])->getValue($node);
                if ($left && $right) {
                    $count = ($right - $left - 1) / 2;
                }
            }
        } else {
            $dql = "SELECT COUNT(node.{$nodeId}) FROM " . $this->meta->rootEntityName . " node";
            if ($direct) {
                $dql .= ' WHERE node.' . $config['parent'] . ' IS NULL';
            }
            $q = $this->em->createQuery($dql);
            $count = intval($q->getSingleScalarResult());
        }
        return $count;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getChildren($node, $direct, $sortByField, $direction)
    {
        $config = $this->listener->getConfiguration($this->em, $this->meta->name);
             
        $qb = $this->em->createQueryBuilder();
        $qb->select('node')
            ->from($this->meta->rootEntityName, 'node');
        if ($node !== null) {
            if ($direct) {
                $nodeId = $this->meta->getSingleIdentifierFieldName();
                $id = $this->meta->getReflectionProperty($nodeId)->getValue($node);
                $qb->where('node.' . $config['parent'] . ' = ' . $id);
            } else {
                $left = $this->meta->getReflectionProperty($config['left'])->getValue($node);
                $right = $this->meta->getReflectionProperty($config['right'])->getValue($node);
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
            if ($this->meta->hasField($sortByField) && in_array(strtolower($direction), array('asc', 'desc'))) {
                $qb->orderBy('node.' . $sortByField, $direction);
            } else {
                throw new \Gedmo\Exception\InvalidArgumentException("Invalid sort options specified: field - {$sortByField}, direction - {$direction}");
            }
        }
        $q = $qb->getQuery();
        $q->useResultCache(false);
        $q->useQueryCache(false);
        return $q->getResult(Query::HYDRATE_OBJECT);
    }

    /**
     * {@inheritdoc}
     */
    public function getTreeLeafs($sortByField, $direction)
    {
        $config = $this->listener->getConfiguration($this->em, $this->meta->name);

        $qb = $this->em->createQueryBuilder();
        $qb->select('node')
            ->from($this->meta->rootEntityName, 'node')
            ->where('node.' . $config['right'] . ' = 1 + node.' . $config['left']);
        if (!$sortByField) {
            $qb->orderBy('node.' . $config['left'], 'ASC');
        } else {
            if ($this->meta->hasField($sortByField) && in_array(strtolower($direction), array('asc', 'desc'))) {
                $qb->orderBy('node.' . $sortByField, $direction);
            } else {
                throw new \Gedmo\Exception\InvalidArgumentException("Invalid sort options specified: field - {$sortByField}, direction - {$direction}");
            }
        }
        $q = $qb->getQuery();
        return $q->getResult(Query::HYDRATE_OBJECT);
    }
    
    /**
     * {@inheritdoc}
     */
    public function moveNodeDown($node, $number)
    {
        $config = $this->listener->getConfiguration($this->em, $this->meta->name);
        if (!$number) {
            return false;
        }
        
        $parent = $this->meta->getReflectionProperty($config['parent'])->getValue($node);
        $right = $this->meta->getReflectionProperty($config['right'])->getValue($node);
        
        if ($parent) {
            $this->em->refresh($parent);
            $parentRight = $this->meta->getReflectionProperty($config['right'])->getValue($parent);
            if (($right + 1) == $parentRight) {
                return false;
            }
        }
        $dql = "SELECT node FROM {$this->meta->rootEntityName} node";
        $dql .= ' WHERE node.' . $config['left'] . ' = ' . ($right + 1);
        $q = $this->em->createQuery($dql);
        $q->setMaxResults(1);
        $result = $q->getResult(Query::HYDRATE_OBJECT);
        $nextSiblingNode = count($result) ? array_shift($result) : null;
        
        if (!$nextSiblingNode) {
            return false;
        }
        
        // this one is very important because if em is not cleared
        // it loads node from memory without refresh
        $this->em->refresh($nextSiblingNode);
        
        $left = $this->meta->getReflectionProperty($config['left'])->getValue($node);
        $nextLeft = $this->meta->getReflectionProperty($config['left'])->getValue($nextSiblingNode);
        $nextRight = $this->meta->getReflectionProperty($config['right'])->getValue($nextSiblingNode);
        $edge = $this->listener->getStrategy()->getTreeEdge($this->em, $node);
        // process updates in transaction
        $this->em->getConnection()->beginTransaction();
        try {            
            $this->listener->getStrategy()->synchronize($this->em, $node, $edge - $left + 1, '+', 'BETWEEN ' . $left . ' AND ' . $right);
            $this->listener->getStrategy()->synchronize($this->em, $node, $nextLeft - $left, '-', 'BETWEEN ' . $nextLeft . ' AND ' . $nextRight);
            $this->listener->getStrategy()->synchronize($this->em, $node, $edge - $left - ($nextRight - $nextLeft), '-', ' > ' . $edge);
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->close();
            $this->em->getConnection()->rollback();
            throw new \Gedmo\Exception\RuntimeException('Transaction failed', null, $e);
        }
        if (is_int($number)) {
            $number--;
        }
        if ($number) {
            $this->em->refresh($node);
            $this->moveNodeDown($node, $number);
        }
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function moveNodeUp($node, $number)
    {
        $config = $this->listener->getConfiguration($this->em, $this->meta->name);
        if (!$number) {
            return false;
        }
        
        $parent = $this->meta->getReflectionProperty($config['parent'])->getValue($node);            
        $left = $this->meta->getReflectionProperty($config['left'])->getValue($node);
        if ($parent) {
            $this->em->refresh($parent);
            $parentLeft = $this->meta->getReflectionProperty($config['left'])->getValue($parent);
            if (($left - 1) == $parentLeft) {
                return false;
            }
        }
        
        $dql = "SELECT node FROM {$this->meta->rootEntityName} node";
        $dql .= ' WHERE node.' . $config['right'] . ' = ' . ($left - 1);
        $q = $this->em->createQuery($dql);
        $q->setMaxResults(1);
        $result = $q->getResult(Query::HYDRATE_OBJECT);
        $previousSiblingNode = count($result) ? array_shift($result) : null;
        
        if (!$previousSiblingNode) {
            return false;
        }
        // this one is very important because if em is not cleared
        // it loads node from memory without refresh
        $this->em->refresh($previousSiblingNode);
        
        $right = $this->meta->getReflectionProperty($config['right'])->getValue($node);
        $previousLeft = $this->meta->getReflectionProperty($config['left'])->getValue($previousSiblingNode);
        $previousRight = $this->meta->getReflectionProperty($config['right'])->getValue($previousSiblingNode);
        $edge = $this->listener->getStrategy()->getTreeEdge($this->em, $node);
        // process updates in transaction
        $this->em->getConnection()->beginTransaction();
        try {
            $this->listener->getStrategy()->synchronize($this->em, $node, $edge - $previousLeft +1, '+', 'BETWEEN ' . $previousLeft . ' AND ' . $previousRight);
            $this->listener->getStrategy()->synchronize($this->em, $node, $left - $previousLeft, '-', 'BETWEEN ' .$left . ' AND ' . $right);
            $this->listener->getStrategy()->synchronize($this->em, $node, $edge - $previousLeft - ($right - $left), '-', '> ' . $edge);
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->close();
            $this->em->getConnection()->rollback();
            throw new \Gedmo\Exception\RuntimeException('Transaction failed', null, $e);
        }
        if (is_int($number)) {
            $number--;
        }
        if ($number) {
            $this->em->refresh($node);
            $this->moveNodeUp($node, $number);
        }
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function reorder($node, $sortByField, $direction, $verify)
    {
        $config = $this->listener->getConfiguration($this->em, $this->meta->name);
        if ($verify && is_array($this->verifyTreeConsistence())) {
            return false;
        }
               
        $nodes = $this->getChildren($node, true, $sortByField, $direction);
        foreach ($nodes as $node) {
            // this is overhead but had to be refreshed
            $this->em->refresh($node);
            $right = $this->meta->getReflectionProperty($config['right'])->getValue($node);
            $left = $this->meta->getReflectionProperty($config['left'])->getValue($node);
            $this->moveNodeDown($node, true);
            if ($left != ($right - 1)) {
                $this->reorder($node, $sortByField, $direction, false);
            }
        }
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function removeNodeFromTree($node)
    {
        $config = $this->listener->getConfiguration($this->em, $this->meta->name);
        
        $right = $this->meta->getReflectionProperty($config['right'])->getValue($node);
        $left = $this->meta->getReflectionProperty($config['left'])->getValue($node);
        $parent = $this->meta->getReflectionProperty($config['parent'])->getValue($node);
            
        if ($right == $left + 1) {
            $this->em->remove($node);
            $this->em->flush();
            return;
        }
        // process updates in transaction
        $this->em->getConnection()->beginTransaction();
        try {
            $this->em->refresh($parent);
            $pk = $this->meta->getSingleIdentifierFieldName();
            $parentId = $this->meta->getReflectionProperty($pk)->getValue($parent);
            $nodeId = $this->meta->getReflectionProperty($pk)->getValue($node);
            
            $dql = "UPDATE {$this->meta->rootEntityName} node";
            $dql .= ' SET node.' . $config['parent'] . ' = ' . $parentId;
            $dql .= ' WHERE node.' . $config['parent'] . ' = ' . $nodeId;
            $q = $this->em->createQuery($dql);
            $q->getSingleScalarResult();
            
            $this->listener->getStrategy()->synchronize($this->em, $node, 1, '-', 'BETWEEN ' . ($left + 1) . ' AND ' . ($right - 1));
            $this->listener->getStrategy()->synchronize($this->em, $node, 2, '-', '> ' . $right);
            
            $dql = "UPDATE {$this->meta->rootEntityName} node";
            $dql .= ' SET node.' . $config['parent'] . ' = NULL,';
            $dql .= ' node.' . $config['left'] . ' = 0,';
            $dql .= ' node.' . $config['right'] . ' = 0';
            $dql .= ' WHERE node.' . $pk . ' = ' . $nodeId;
            $q = $this->em->createQuery($dql);
            $q->getSingleScalarResult();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->close();
            $this->em->getConnection()->rollback();
            throw new \Gedmo\Exception\RuntimeException('Transaction failed', null, $e);
        }
        $this->em->refresh($node);
        $this->em->remove($node);
        $this->em->flush();
    }
    
    /**
     * {@inheritdoc}
     */
    public function verifyTreeConsistence()
    {
        if (!$this->countChildren(null, false)) {
            return true; // tree is empty
        }
        $errors = array();
        $config = $this->listener->getConfiguration($this->em, $this->meta->name);
        $identifier = $this->meta->getSingleIdentifierFieldName();
        $leftField = $config['left'];
        $rightField = $config['right'];
        $parentField = $config['parent'];
        
        $q = $this->em->createQuery("SELECT MIN(node.{$leftField}) FROM {$this->meta->rootEntityName} node");
        
        $min = intval($q->getSingleScalarResult());
        $edge = $this->listener->getStrategy()->getTreeEdge($this->em, new $this->meta->name());
        for ($i = $min; $i <= $edge; $i++) {
            $dql = "SELECT COUNT(node.{$identifier}) FROM {$this->meta->rootEntityName} node";
            $dql .= " WHERE (node.{$leftField} = {$i} OR node.{$rightField} = {$i})";
            $q = $this->em->createQuery($dql);
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
        $dql = "SELECT c FROM {$this->meta->rootEntityName} c";
        $dql .= " LEFT JOIN c.{$parentField} p";
        $dql .= " WHERE c.{$parentField} IS NOT NULL";
        $dql .= " AND p.{$identifier} IS NULL";
        $q = $this->em->createQuery($dql);
        $nodes = $q->getArrayResult();
        if (count($nodes)) {
            foreach ($nodes as $node) {
                $errors[] = "node [{$node[$identifier]}] has missing parent";
            }
            return $errors; // loading broken relation can cause infinite loop
        }
        
        $dql = "SELECT node FROM {$this->meta->rootEntityName} node";
        $dql .= " WHERE node.{$rightField} < node.{$leftField}";
        $q = $this->em->createQuery($dql);
        $q->setMaxResults(1);
        $result = $q->getResult(Query::HYDRATE_OBJECT);
        $node = count($result) ? array_shift($result) : null; 
        
        if ($node) {
            $id = $this->meta->getReflectionProperty($identifier)->getValue($node);
            $errors[] = "node [{$id}], left is greater than right";
        }
        
        $dql = "SELECT node FROM {$this->meta->rootEntityName} node";
        $q = $this->em->createQuery($dql);
        $nodes = $q->getResult(Query::HYDRATE_OBJECT);
        
        foreach ($nodes as $node) {
            $right = $this->meta->getReflectionProperty($rightField)->getValue($node);
            $left = $this->meta->getReflectionProperty($leftField)->getValue($node);
            $id = $this->meta->getReflectionProperty($identifier)->getValue($node);
            $parent = $this->meta->getReflectionProperty($parentField)->getValue($node);
            if (!$right || !$left) {
                $errors[] = "node [{$id}] has invalid left or right values";
            } elseif ($right == $left) {
                $errors[] = "node [{$id}] has identical left and right values";
            } elseif ($parent) {
                $this->em->refresh($parent);
                $parentRight = $this->meta->getReflectionProperty($rightField)->getValue($parent);
                $parentLeft = $this->meta->getReflectionProperty($leftField)->getValue($parent);
                $parentId = $this->meta->getReflectionProperty($identifier)->getValue($parent);
                if ($left < $parentLeft) {
                    $errors[] = "node [{$id}] left is less than parent`s [{$parentId}] left value";
                } elseif ($right > $parentRight) {
                    $errors[] = "node [{$id}] right is greater than parent`s [{$parentId}] right value";
                }
            } else {
                $dql = "SELECT COUNT(node.{$identifier}) FROM {$this->meta->rootEntityName} node";
                $dql .= " WHERE node.{$leftField} < {$left}";
                $dql .= " AND node.{$rightField} > {$right}";
                $q = $this->em->createQuery($dql);
                if ($count = intval($q->getSingleScalarResult())) {
                    $errors[] = "node [{$id}] parent field is blank, but it has a parent";
                }
            }
        }
        return $errors ?: true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function recoverTree()
    {
        if ($this->verifyTreeConsistence() === true) {
            return;
        }
        
        $config = $this->listener->getConfiguration($this->em, $this->meta->name);
        
        $identifier = $this->meta->getSingleIdentifierFieldName();
        $leftField = $config['left'];
        $rightField = $config['right'];
        $parentField = $config['parent'];
        
        $count = 1;
        $dql = "SELECT node.{$identifier} FROM {$this->meta->rootEntityName} node";
        $dql .= " ORDER BY node.{$leftField} ASC";
        $q = $this->em->createQuery($dql);
        $nodes = $q->getArrayResult();
        // process updates in transaction
        $this->em->getConnection()->beginTransaction();
        try {
            foreach ($nodes as $node) {
                $left = $count++;
                $right = $count++;
                $dql = "UPDATE {$this->meta->rootEntityName} node";
                $dql .= " SET node.{$leftField} = {$left},";
                $dql .= " node.{$rightField} = {$right}";
                $dql .= " WHERE node.{$identifier} = {$node[$identifier]}";
                $q = $this->em->createQuery($dql);
                $q->getSingleScalarResult();
            }
            foreach ($nodes as $node) {
                $node = $this->em->getReference($this->meta->name, $node[$identifier]);
                $this->em->refresh($node);
                $parent = $this->meta->getReflectionProperty($parentField)->getValue($node);
                $this->listener->getStrategy()->adjustNodeWithParent($parent, $node, $this->em);
            }
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->close();
            $this->em->getConnection()->rollback();
            throw new \Gedmo\Exception\RuntimeException('Transaction failed', null, $e);
        }
    }
}