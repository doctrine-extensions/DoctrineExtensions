<?php

namespace DoctrineExtensions\Tree;

use Doctrine\Common\EventSubscriber,
    Doctrine\ORM\Events,
    Doctrine\ORM\Event\LifecycleEventArgs,
    Doctrine\ORM\Event\OnFlushEventArgs,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\Query;

/**
 * The tree listener handles the synchronization of
 * tree nodes for entities which implements
 * the Node interface.
 * 
 * This behavior can inpact the performance of your application
 * since nested set trees are slow on inserts and updates.
 * 
 * Some Tree logic is copied from -
 * CakePHP: Rapid Development Framework (http://cakephp.org)
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package DoctrineExtensions.Tree
 * @subpackage TreeListener
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TreeListener implements EventSubscriber
{
    /**
     * List of cached entity configurations
     *  
     * @var array
     */
    protected $_configurations = array();
    
    /**
     * The max number of "right" field of the
     * tree in case few root nodes will be persisted
     * on one flush
     * 
     * @var integer
     */
    protected $_treeEdge = 0;
    
    /**
     * List of pending Nodes, which needs to
     * be post processed because of having a parent Node
     * which requires some additional calculations
     * 
     * @var array
     */
    protected $_pendingChildNodeInserts = array();
    
    /**
     * List of pending Nodes, which needs to wait
     * till all inserts are processed first
     * 
     * @var array
     */
    protected $_pendingNodeUpdates = array();
    
    /**
     * List of valid Node entity classes
     * 
     * @var array
     */
    protected $_validatedNodeClasses = array();
    
    /**
     * Get the configuration for entity
     * 
     * @param Node $entity
     * @return Configuration
     */
    public function getConfiguration(Node $entity)
    {
        $entityClass = get_class($entity);
        if (!isset($this->_configurations[$entityClass])) {
            $this->_configurations[$entityClass] = $entity->getTreeConfiguration();
        }
        return $this->_configurations[$entityClass];
    }
    
    /**
     * Specifies the list of events to listen
     * 
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            Events::prePersist,
            Events::postPersist,
            Events::preRemove,
            Events::onFlush
        );
    }
    
    /**
     * Looks for Node entities being updated
     * for further processing
     * 
     * @param OnFlushEventArgs $args
     * @return void
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        // check all scheduled updates for TreeNodes
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof Node) {
                $config = $this->getConfiguration($entity);
                $meta = $em->getClassMetadata(get_class($entity));
                $changeSet = $uow->getEntityChangeSet($entity);
                if (array_key_exists($config->getParentField(), $changeSet)) {
                    $this->_validateNodeClass($entity, $em);
                    if ($uow->hasPendingInsertions()) {
                        $this->_pendingNodeUpdates[] = $entity;
                    } else {
                        $parent = $meta->getReflectionProperty($config->getParentField())
                            ->getValue($entity);
                        $this->_adjustNodeWithParent($parent, $entity, $em);
                    }
                }
            }
        }
        // reset the tree edge
        $this->_treeEdge = 0;
    }
    
    /**
     * Updates tree on Node removal
     * 
     * @param LifecycleEventArgs $args
     * @return void
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        $em = $args->getEntityManager();
        $entity = $args->getEntity();
        
        if ($entity instanceof Node) {
            $this->_validateNodeClass($entity, $em);
            $uow = $em->getUnitOfWork();
            
            $config = $this->getConfiguration($entity);
            $entityClass = get_class($entity);
            $meta = $em->getClassMetadata($entityClass);
            
            $leftValue = $meta->getReflectionProperty($config->getLeftField())
                ->getValue($entity);
            $rightValue = $meta->getReflectionProperty($config->getRightField())
                 ->getValue($entity);
            
            if (!$leftValue || !$rightValue) {
                return;
            }
            $diff = $rightValue - $leftValue + 1;
            if ($diff > 2) {
                $leftField = $config->getLeftField();
                $dql = "SELECT node FROM {$entityClass} node";
                $dql .= " WHERE node.{$leftField} BETWEEN :left AND :right";
                $q = $em->createQuery($dql);
                // get nodes for deletion
                $q->setParameter('left', $leftValue + 1);
                $q->setParameter('right', $rightValue - 1);
                $nodes = $q->getResult();
                foreach ($nodes as $node) {
                    $uow->scheduleForDelete($node);
                }
            }
            $this->_sync($em, $entity, $diff, '-', '> ' . $rightValue);
        }
    }
    
    /**
     * Checks for pending Nodes to fully synchronize
     * the tree
     * 
     * @param LifecycleEventArgs $args
     * @return void
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $em = $args->getEntityManager();
        $entity = $args->getEntity();
        $uow = $em->getUnitOfWork();
        
        if (!$uow->hasPendingInsertions()) {
            while ($entity = array_shift($this->_pendingChildNodeInserts)) {
                $this->_processPendingNode($em, $entity);
            }
            
            while ($entity = array_shift($this->_pendingNodeUpdates)) {
                $this->_processPendingNode($em, $entity);
            }
        }
    }
    
    /**
     * Checks for persisted Nodes
     * 
     * @param LifecycleEventArgs $args
     * @return void
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $em = $args->getEntityManager();
        $entity = $args->getEntity();
        $uow = $em->getUnitOfWork();
        
        if ($entity instanceof Node) {
            $this->_validateNodeClass($entity, $em);
            $config = $this->getConfiguration($entity);
            $meta = $em->getClassMetadata(get_class($entity));
            $parent = $meta->getReflectionProperty($config->getParentField())->getValue($entity);
            if ($parent === null) { // instanceof Node
                $this->_prepareRoot($em, $entity);
            } else {
                $meta->getReflectionProperty($config->getLeftField())
                    ->setValue($entity, 0);
                $meta->getReflectionProperty($config->getRightField())
                    ->setValue($entity, 0);
                $this->_pendingChildNodeInserts[] = $entity;
            }
        }
    }
    
    /**
     * Validates the given Node entity class
     * 
     * @param Node $entity
     * @param EntityManager $em
     * @throws Tree\Exception if configuration is invalid
     * @return void
     */
    protected function _validateNodeClass(Node $entity, EntityManager $em)
    {
        $entityClass = get_class($entity);
        if (isset($this->_validatedNodeClasses[$entityClass])) {
            return;
        }
        $config = $this->getConfiguration($entity);
        $meta = $em->getClassMetadata($entityClass);
        
        // left field
        if (!isset($meta->reflFields[$config->getLeftField()])) {
            throw Exception::cannotFindLeftField($config->getLeftField(), $entityClass);
        }
        
        // right field
        if (!isset($meta->reflFields[$config->getRightField()])) {
            throw Exception::cannotFindRightField($config->getRightField(), $entityClass);
        }
        
        // parent field
        $parent = $config->getParentField();
        if (!isset($meta->reflFields[$parent])) {
            throw Exception::cannotFindParentField($parent, $entityClass);
        }
        
        if (!isset($meta->associationMappings[$parent]) ||
            $meta->associationMappings[$parent]['targetEntity'] != $entityClass
        ) {
            throw Exception::parentFieldNotRelated($parent, $entityClass);
        }
        
        $this->_validatedNodeClasses[$entityClass] = null;
    }
    
    /**
     * Synchronize tree with Node parent
     * 
     * @param EntityManager $em
     * @param Node $entity
     * @return void
     */
    private function _processPendingNode(EntityManager $em, Node $entity)
    {
        $config = $this->getConfiguration($entity);
        $meta = $em->getClassMetadata(get_class($entity));
        $parent = $meta->getReflectionProperty($config->getParentField())->getValue($entity);
        $this->_adjustNodeWithParent($parent, $entity, $em);
    }
    
    /**
     * If Node does not have parent set it as root
     * 
     * @param EntityManager $em
     * @param Node $entity
     * @return void
     */
    private function _prepareRoot(EntityManager $em, Node $entity)
    {
        $config = $this->getConfiguration($entity);
        $edge = $this->_treeEdge ?: $this->_getTreeEdge($em, $entity);
        $meta = $em->getClassMetadata(get_class($entity));
        
        $meta->getReflectionProperty($config->getLeftField())
            ->setValue($entity, $edge + 1);
            
        $meta->getReflectionProperty($config->getRightField())
            ->setValue($entity, $edge + 2);
            
        $this->_treeEdge = $edge + 2;
    }
    
    /**
     * Synchronize tree according to Node`s parent Node
     * 
     * @param Node $parent
     * @param Node $entity
     * @param EntityManager $em
     * @return void
     */
    private function _adjustNodeWithParent($parent, Node $entity, EntityManager $em)
    {
        $config = $this->getConfiguration($entity);
        $edge = $this->_getTreeEdge($em, $entity);
        $meta = $em->getClassMetadata(get_class($entity));
        $leftValue = $meta->getReflectionProperty($config->getLeftField())->getValue($entity);
        $rightValue = $meta->getReflectionProperty($config->getRightField())->getValue($entity);
        if ($parent === null) {
            $this->_sync($em, $entity, $edge - $leftValue + 1, '+', 'BETWEEN ' . $leftValue . ' AND ' . $rightValue);
            $this->_sync($em, $entity, $rightValue - $leftValue + 1, '-', '> ' . $leftValue);
        } else {
            // need to refresh the parent to get up to date left and right
            $em->refresh($parent);
            $parentLeftValue = $meta->getReflectionProperty($config->getLeftField())->getValue($parent);
            $parentRightValue = $meta->getReflectionProperty($config->getRightField())->getValue($parent);
            if ($leftValue < $parentLeftValue && $parentRightValue < $rightValue) {
                return;
            }
            if (empty($leftValue) && empty($rightValue)) {
                $this->_sync($em, $entity, 2, '+', '>= ' . $parentRightValue);
                $entityClass = get_class($entity);
                // cannot schedule this update if other Nodes pending
                $qb = $em->createQueryBuilder();
                $qb->update($entityClass, 'node')
                    ->set('node.' . $config->getLeftField(), $parentRightValue)
                    ->set('node.' . $config->getRightField(), $parentRightValue + 1);
                $entityIdentifiers = $meta->getIdentifierValues($entity);
                foreach ($entityIdentifiers as $field => $value) {
                    if (strlen($value)) {
                        $qb->where('node.' . $field . ' = ' . $value);
                    }
                }
                $q = $qb->getQuery();
                $q->getSingleScalarResult();
            } else {
                $this->_sync($em, $entity, $edge - $leftValue + 1, '+', 'BETWEEN ' . $leftValue . ' AND ' . $rightValue);
                $diff = $rightValue - $leftValue + 1;
                
                if ($leftValue > $parentLeftValue) {
                    if ($rightValue < $parentRightValue) {
                        $this->_sync($em, $entity, $diff, '-', 'BETWEEN ' . $rightValue . ' AND ' . ($parentRightValue - 1));
                        $this->_sync($em, $entity, $edge - $parentRightValue + $diff + 1, '-', '> ' . $edge);
                    } else {
                        $this->_sync($em, $entity, $diff, '+', 'BETWEEN ' . $parentRightValue . ' AND ' . $rightValue);
                        $this->_sync($em, $entity, $edge - $parentRightValue + 1, '-', '> ' . $edge);
                    }
                } else {
                    $this->_sync($em, $entity, $diff, '-', 'BETWEEN ' . $rightValue . ' AND ' . ($parentRightValue - 1));
                    $this->_sync($em, $entity, $edge - $parentRightValue + $diff + 1, '-', '> ' . $edge);
                }
            }
        }
        return true;
    }
    
    /**
     * Synchronize the tree with given conditions
     * 
     * @param EntityManager $em
     * @param Node $entity
     * @param integer $shift
     * @param string $dir
     * @param string $conditions
     * @param string $field
     * @return void
     */
    private function _sync(EntityManager $em, Node $entity, $shift, $dir, $conditions, $field = 'both')
    {
        $config = $this->getConfiguration($entity);
        if ($field == 'both') {
            $this->_sync($em, $entity, $shift, $dir, $conditions, $config->getLeftField());
            $field = $config->getRightField();
        }
        $entityClass = get_class($entity);
        
        $dql = "UPDATE {$entityClass} node";
        $dql .= " SET node.{$field} = node.{$field} {$dir} {$shift}";
        $dql .= " WHERE node.{$field} {$conditions}";
        $query = $em->createQuery($dql);
        return $query->getSingleScalarResult();
    }
    
    /**
     * Get the edge of tree
     * 
     * @param EntityManager $em
     * @param Node $entity
     * @return integer
     */
    private function _getTreeEdge(EntityManager $em, Node $entity)
    {
        $config = $this->getConfiguration($entity);
        $entityClass = get_class($entity);
        $right = $config->getRightField();
        $query = $em->createQuery("SELECT MAX(node.{$right}) FROM {$entityClass} node");
        $right = $query->getSingleScalarResult();
        return intval($right);
    }
}