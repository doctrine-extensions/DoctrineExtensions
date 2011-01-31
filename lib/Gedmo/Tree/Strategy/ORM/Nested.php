<?php

namespace Gedmo\Tree\Strategy\ORM;

use Gedmo\Tree\StrategyInterface,
    Doctrine\ORM\EntityManager,
    Gedmo\Tree\AbstractTreeListener;

class Nested implements StrategyInterface
{
    /**
     * The max number of "right" field of the
     * tree in case few root nodes will be persisted
     * on one flush
     * 
     * @var integer
     */
    protected $treeEdge = 0;
    
    /**
     * TreeListener
     * 
     * @var AbstractTreeListener
     */
    protected $listener = null;
    
    /**
     * List of pending Nodes, which needs to
     * be post processed because of having a parent Node
     * which requires some additional calculations
     * 
     * @var array
     */
    protected $pendingChildNodeInserts = array();
    
    /**
     * {@inheritdoc}
     */
    public function __construct(AbstractTreeListener $listener)
    {
        $this->listener = $listener;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'nested';
    }
    
    /**
     * {@inheritdoc}
     */
    public function processScheduledUpdate($em, $entity)
    {
        $entityClass = get_class($entity);
        $config = $this->listener->getConfiguration($em, $entityClass);
        $meta = $em->getClassMetadata($entityClass);
        $uow = $em->getUnitOfWork();
        $changeSet = $uow->getEntityChangeSet($entity);
        if (array_key_exists($config['parent'], $changeSet)) {
            $parent = $meta->getReflectionProperty($config['parent'])->getValue($entity);
            $this->adjustNodeWithParent($parent, $entity, $em);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function processPrePersist($em, $entity)
    {
        $entityClass = get_class($entity);
        $config = $this->listener->getConfiguration($em, $entityClass);
        $meta = $em->getClassMetadata($entityClass);
        
        $parent = $meta->getReflectionProperty($config['parent'])->getValue($entity);
        if ($parent === null) {
            $this->prepareRoot($em, $entity);
            if (isset($config['level'])) {
                $meta->getReflectionProperty($config['level'])->setValue($entity, 0);
            }
        } else {
            $meta->getReflectionProperty($config['left'])->setValue($entity, 0);
            $meta->getReflectionProperty($config['right'])->setValue($entity, 0);
            if (isset($config['level'])) {
                $meta->getReflectionProperty($config['level'])->setValue(
                    $entity,
                    $meta->getReflectionProperty($config['level'])->getValue($parent) + 1
                );
            }
            $this->pendingChildNodeInserts[] = $entity;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function processPostPersist($em, $entity)
    {        
        if (count($this->pendingChildNodeInserts)) {
            while ($entity = array_shift($this->pendingChildNodeInserts)) {
                $meta = $em->getClassMetadata(get_class($entity));
                $config = $this->listener->getConfiguration($em, $meta->name);
                $parent = $meta->getReflectionProperty($config['parent'])->getValue($entity);
                $this->adjustNodeWithParent($parent, $entity, $em);
            }
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function processScheduledDelete($em, $entity)
    {
        $entityClass = get_class($entity);
        $config = $this->listener->getConfiguration($em, $entityClass);
        $uow = $em->getUnitOfWork();
        $meta = $em->getClassMetadata($entityClass);
        
        $leftValue = $meta->getReflectionProperty($config['left'])->getValue($entity);
        $rightValue = $meta->getReflectionProperty($config['right'])->getValue($entity);
        
        if (!$leftValue || !$rightValue) {
            return;
        }
        $diff = $rightValue - $leftValue + 1;
        if ($diff > 2) {
            $dql = "SELECT node FROM {$meta->rootEntityName} node";
            $dql .= " WHERE node.{$config['left']} BETWEEN :left AND :right";
            $q = $em->createQuery($dql);
            // get nodes for deletion
            $q->setParameter('left', $leftValue + 1);
            $q->setParameter('right', $rightValue - 1);
            $nodes = $q->getResult();
            foreach ((array)$nodes as $node) {
                $uow->scheduleForDelete($node);
            }
        }
        $this->synchronize($em, $entity, $diff, '-', '> ' . $rightValue);
    }
    
    /**
     * {@inheritdoc}
     */
    public function onFlushEnd($em)
    {
        // reset the tree edge
        $this->treeEdge = 0;
    }
    
	/**
     * Synchronize tree according to Node`s parent Node
     * 
     * @param object $parent
     * @param object $entity
     * @param EntityManager $em
     * @return void
     */
    public function adjustNodeWithParent($parent, $entity, EntityManager $em)
    {
        $meta = $em->getClassMetadata(get_class($entity));
        $config = $this->listener->getConfiguration($em, $meta->name);
        $edge = $this->getTreeEdge($em, $entity);
        
        
        $leftValue = $meta->getReflectionProperty($config['left'])->getValue($entity);
        $rightValue = $meta->getReflectionProperty($config['right'])->getValue($entity);
        if ($parent === null) {
            $this->synchronize($em, $entity, $edge - $leftValue + 1, '+', 'BETWEEN ' . $leftValue . ' AND ' . $rightValue);
            $this->synchronize($em, $entity, $rightValue - $leftValue + 1, '-', '> ' . $leftValue);
        } else {
            // need to refresh the parent to get up to date left and right
            $em->refresh($parent);
            $parentLeftValue = $meta->getReflectionProperty($config['left'])->getValue($parent);
            $parentRightValue = $meta->getReflectionProperty($config['right'])->getValue($parent);
            if ($leftValue < $parentLeftValue && $parentRightValue < $rightValue) {
                return;
            }
            if (empty($leftValue) && empty($rightValue)) {
                $this->synchronize($em, $entity, 2, '+', '>= ' . $parentRightValue);
                // cannot schedule this update if other Nodes pending
                $qb = $em->createQueryBuilder();
                $qb->update($meta->rootEntityName, 'node')
                    ->set('node.' . $config['left'], $parentRightValue)
                    ->set('node.' . $config['right'], $parentRightValue + 1);
                $entityIdentifiers = $meta->getIdentifierValues($entity);
                foreach ($entityIdentifiers as $field => $value) {
                    if (strlen($value)) {
                        $qb->where('node.' . $field . ' = ' . $value);
                    }
                }
                $q = $qb->getQuery();
                $q->getSingleScalarResult();
            } else {
                $this->synchronize($em, $entity, $edge - $leftValue + 1, '+', 'BETWEEN ' . $leftValue . ' AND ' . $rightValue);
                $diff = $rightValue - $leftValue + 1;
                
                if ($leftValue > $parentLeftValue) {
                    if ($rightValue < $parentRightValue) {
                        $this->synchronize($em, $entity, $diff, '-', 'BETWEEN ' . $rightValue . ' AND ' . ($parentRightValue - 1));
                        $this->synchronize($em, $entity, $edge - $parentRightValue + $diff + 1, '-', '> ' . $edge);
                    } else {
                        $this->synchronize($em, $entity, $diff, '+', 'BETWEEN ' . $parentRightValue . ' AND ' . $rightValue);
                        $this->synchronize($em, $entity, $edge - $parentRightValue + 1, '-', '> ' . $edge);
                    }
                } else {
                    $this->synchronize($em, $entity, $diff, '-', 'BETWEEN ' . $rightValue . ' AND ' . ($parentRightValue - 1));
                    $this->synchronize($em, $entity, $edge - $parentRightValue + $diff + 1, '-', '> ' . $edge);
                }
            }
        }
        return true;
    }
    
    /**
     * Synchronize the tree with given conditions
     * 
     * @param EntityManager $em
     * @param object $entity
     * @param integer $shift
     * @param string $dir
     * @param string $conditions
     * @param string $field
     * @return void
     */
    public function synchronize(EntityManager $em, $entity, $shift, $dir, $conditions, $field = 'both')
    {
        $meta = $em->getClassMetadata(get_class($entity));
        $config = $this->listener->getConfiguration($em, $meta->name);
        if ($field == 'both') {
            $this->synchronize($em, $entity, $shift, $dir, $conditions, $config['left']);
            $field = $config['right'];
        }
        
        $dql = "UPDATE {$meta->rootEntityName} node";
        $dql .= " SET node.{$field} = node.{$field} {$dir} {$shift}";
        $dql .= " WHERE node.{$field} {$conditions}";
        $q = $em->createQuery($dql);
        return $q->getSingleScalarResult();
    }
    
    /**
     * Get the edge of tree
     * 
     * @param EntityManager $em
     * @param object $entity
     * @return integer
     */
    public function getTreeEdge(EntityManager $em, $entity)
    {
        $meta = $em->getClassMetadata(get_class($entity));
        $config = $this->listener->getConfiguration($em, $meta->name);
        
        $query = $em->createQuery("SELECT MAX(node.{$config['right']}) FROM {$meta->rootEntityName} node");
        $right = $query->getSingleScalarResult();
        return intval($right);
    }
    
	/**
     * If Node does not have parent set it as root
     * 
     * @param EntityManager $em
     * @param object $entity
     * @return void
     */
    private function prepareRoot(EntityManager $em, $entity)
    {
        $meta = $em->getClassMetadata(get_class($entity));
        $config = $this->listener->getConfiguration($em, $meta->name);
        
        $edge = $this->treeEdge ?: $this->getTreeEdge($em, $entity);

        $meta->getReflectionProperty($config['left'])->setValue($entity, $edge + 1);
        $meta->getReflectionProperty($config['right'])->setValue($entity, $edge + 2);
        $this->treeEdge = $edge + 2;
    }
}