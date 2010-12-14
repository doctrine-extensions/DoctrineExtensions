<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventSubscriber,
    Doctrine\ORM\Events,
    Doctrine\ORM\Event\LifecycleEventArgs,
    Doctrine\ORM\Event\OnFlushEventArgs,
    Doctrine\ORM\Event\LoadClassMetadataEventArgs,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\Query,
    Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\ORM\Mapping\ClassMetadataInfo,
    Gedmo\Mapping\ExtensionMetadataFactory;

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
 * @package Gedmo.Tree
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
     * ExtensionMetadataFactory used to read the extension
     * metadata
     * 
     * @var Gedmo\Mapping\ExtensionMetadataFactory
     */
    protected $_extensionMetadataFactory = null;
    
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
            Events::onFlush,
            Events::loadClassMetadata
        );
    }
    
    /**
     * Get the configuration for specific entity class
     * if cache driver is present it scans it also
     * 
     * @param EntityManager $em
     * @param string $class
     * @return array
     */
    public function getConfiguration(EntityManager $em, $class) {
        $config = array();
        if (isset($this->_configurations[$class])) {
            $config = $this->_configurations[$class];
        } else {
            $cacheDriver = $em->getMetadataFactory()->getCacheDriver();
            $cacheId = ExtensionMetadataFactory::getCacheId($class, __NAMESPACE__);
            if (($cached = $cacheDriver->fetch($cacheId)) !== false) {
                $this->_configurations[$class] = $cached;
                $config = $cached;
            }
        }
        return $config;
    }
    
    /**
     * Get metadata mapping reader
     * 
     * @param EntityManager $em
     * @return Gedmo\Mapping\MetadataReader
     */
    public function getExtensionMetadataFactory(EntityManager $em)
    {
        if (null === $this->_extensionMetadataFactory) {
            $this->_extensionMetadataFactory = new ExtensionMetadataFactory($em, __NAMESPACE__);
        }
        return $this->_extensionMetadataFactory;
    }
    
    /**
     * Scans the entities for Tree annotations
     * 
     * @param LoadClassMetadataEventArgs $eventArgs
     * @throws Tree\Exception if any mapping data is invalid
     * @throws RuntimeException if ORM version is old
     * @return void
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $meta = $eventArgs->getClassMetadata();
        $em = $eventArgs->getEntityManager();
        $factory = $this->getExtensionMetadataFactory($em);
        $config = $factory->getExtensionMetadata($meta);
        if ($config) {
            $this->_configurations[$meta->name] = $config;
        }
    }
    
    /**
     * Looks for Tree entities being updated
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
            $entityClass = get_class($entity);
            if ($config = $this->getConfiguration($em, $entityClass)) {
                $meta = $em->getClassMetadata($entityClass);
                $changeSet = $uow->getEntityChangeSet($entity);
                if (array_key_exists($config['parent'], $changeSet)) {
                    if ($uow->hasPendingInsertions()) {
                        $this->_pendingNodeUpdates[] = $entity;
                    } else {
                        $parent = $meta->getReflectionProperty($config['parent'])
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
        $entityClass = get_class($entity);
        
        if ($config = $this->getConfiguration($em, $entityClass)) {
            $uow = $em->getUnitOfWork();
            $meta = $em->getClassMetadata($entityClass);
            
            $leftValue = $meta->getReflectionProperty($config['left'])->getValue($entity);
            $rightValue = $meta->getReflectionProperty($config['right'])->getValue($entity);
            
            if (!$leftValue || !$rightValue) {
                return;
            }
            $diff = $rightValue - $leftValue + 1;
            if ($diff > 2) {
                $dql = "SELECT node FROM {$entityClass} node";
                $dql .= " WHERE node.{$config['left']} BETWEEN :left AND :right";
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
        $entityClass = get_class($entity);
        
        if ($config = $this->getConfiguration($em, $entityClass)) {
            $meta = $em->getClassMetadata($entityClass);
            $parent = $meta->getReflectionProperty($config['parent'])->getValue($entity);
            if ($parent === null) {
                $this->_prepareRoot($em, $entity);
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
                $this->_pendingChildNodeInserts[] = $entity;
            }
        }
    }
    
    /**
     * Checks if $field type is valid
     * 
     * @param ClassMetadata $meta
     * @param string $field
     * @return boolean
     */
    protected function _isValidField(ClassMetadata $meta, $field)
    {
        return in_array($meta->getTypeOfField($field), $this->_validTypes);
    }
    
    /**
     * Synchronize tree with Node parent
     * 
     * @param EntityManager $em
     * @param object $entity
     * @return void
     */
    private function _processPendingNode(EntityManager $em, $entity)
    {
        $entityClass = get_class($entity);
        $config = $this->getConfiguration($em, $entityClass);
        $meta = $em->getClassMetadata($entityClass);
        $parent = $meta->getReflectionProperty($config['parent'])->getValue($entity);
        $this->_adjustNodeWithParent($parent, $entity, $em);
    }
    
    /**
     * If Node does not have parent set it as root
     * 
     * @param EntityManager $em
     * @param object $entity
     * @return void
     */
    private function _prepareRoot(EntityManager $em, $entity)
    {
        $entityClass = get_class($entity);
        $config = $this->getConfiguration($em, $entityClass);
        
        $edge = $this->_treeEdge ?: $this->_getTreeEdge($em, $entity);
        $meta = $em->getClassMetadata($entityClass);
        
        $meta->getReflectionProperty($config['left'])->setValue($entity, $edge + 1);
        $meta->getReflectionProperty($config['right'])->setValue($entity, $edge + 2);
        
        $this->_treeEdge = $edge + 2;
    }
    
    /**
     * Synchronize tree according to Node`s parent Node
     * 
     * @param Node $parent
     * @param object $entity
     * @param EntityManager $em
     * @return void
     */
    private function _adjustNodeWithParent($parent, $entity, EntityManager $em)
    {
        $entityClass = get_class($entity);
        $config = $this->getConfiguration($em, $entityClass);
        $edge = $this->_getTreeEdge($em, $entity);
        $meta = $em->getClassMetadata($entityClass);
        
        $leftValue = $meta->getReflectionProperty($config['left'])->getValue($entity);
        $rightValue = $meta->getReflectionProperty($config['right'])->getValue($entity);
        if ($parent === null) {
            $this->_sync($em, $entity, $edge - $leftValue + 1, '+', 'BETWEEN ' . $leftValue . ' AND ' . $rightValue);
            $this->_sync($em, $entity, $rightValue - $leftValue + 1, '-', '> ' . $leftValue);
        } else {
            // need to refresh the parent to get up to date left and right
            $em->refresh($parent);
            $parentLeftValue = $meta->getReflectionProperty($config['left'])->getValue($parent);
            $parentRightValue = $meta->getReflectionProperty($config['right'])->getValue($parent);
            if ($leftValue < $parentLeftValue && $parentRightValue < $rightValue) {
                return;
            }
            if (empty($leftValue) && empty($rightValue)) {
                $this->_sync($em, $entity, 2, '+', '>= ' . $parentRightValue);
                // cannot schedule this update if other Nodes pending
                $qb = $em->createQueryBuilder();
                $qb->update($entityClass, 'node')
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
     * @param object $entity
     * @param integer $shift
     * @param string $dir
     * @param string $conditions
     * @param string $field
     * @return void
     */
    private function _sync(EntityManager $em, $entity, $shift, $dir, $conditions, $field = 'both')
    {
        $entityClass = get_class($entity);
        $config = $this->getConfiguration($em, $entityClass);
        if ($field == 'both') {
            $this->_sync($em, $entity, $shift, $dir, $conditions, $config['left']);
            $field = $config['right'];
        }
        
        $dql = "UPDATE {$entityClass} node";
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
    private function _getTreeEdge(EntityManager $em, $entity)
    {
        $entityClass = get_class($entity);
        $config = $this->getConfiguration($em, $entityClass);
        
        $query = $em->createQuery("SELECT MAX(node.{$config['right']}) FROM {$entityClass} node");
        $right = $query->getSingleScalarResult();
        return intval($right);
    }
}