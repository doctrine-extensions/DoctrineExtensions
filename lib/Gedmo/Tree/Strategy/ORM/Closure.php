<?php

namespace Gedmo\Tree\Strategy\ORM;

use Gedmo\Tree\Strategy,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\Proxy\Proxy,
    Gedmo\Tree\AbstractTreeListener;

/**
 * This strategy makes tree act like
 * nested set.
 * 
 * This behavior can inpact the performance of your application
 * since nested set trees are slow on inserts and updates.
 * 
 * Some Tree logic is copied from -
 * CakePHP: Rapid Development Framework (http://cakephp.org)
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree.Strategy.ORM
 * @subpackage Nested
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Closure implements Strategy
{   
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
        return Strategy::CLOSURE;
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
            $this->updateNode($em, $entity, $changeSet[$config['parent']]);
        }
    }
    
    public function updateNode(EntityManager $em, $entity, array $change)
    {
        $meta = $em->getClassMetadata(get_class($entity));
        $config = $this->listener->getConfiguration($em, $meta->name);
        $closureMeta = $em->getClassMetadata($config['closure']);
        
        $oldParent = $change[0];
        $nodeId = $this->extractIdentifier($em, $entity);
        //$oldParentId = $this->extractIdentifier($em, $oldParent);
        
        $table = $closureMeta->getTableName();
        if ($oldParent) {
            // requires single identifier in closure table 
            $subselect = "SELECT c2.id FROM {$table} c1";
            $subselect .= " INNER JOIN {$table} c2 ON c1.descendant = c2.descendant";
            $subselect .= " WHERE c1.ancestor = :id";
            $subselect .= " AND c2.depth > c1.depth";
            
            $sql = "DELETE FROM {$table}";
            $sql .= " WHERE id IN({$subselect})";
            if (!$em->getConnection()->executeQuery($sql, array('id' => $nodeId))) {
                throw new \Gedmo\Exception\RuntimeException('Failed to delete old Closure records');
            }
        }
        
        //\Doctrine\Common\Util\Debug::dump($oldParent);
        //die();
    }
    
    /**
     * {@inheritdoc}
     */
    public function processPrePersist($em, $entity)
    {
        $this->pendingChildNodeInserts[] = $entity;
    }
    
    /**
     * {@inheritdoc}
     */
    public function processPostPersist($em, $entity)
    {        
        if (count($this->pendingChildNodeInserts)) {
            while ($entity = array_shift($this->pendingChildNodeInserts)) {
                $this->insertNode($em, $entity);
            }
            
            //$meta = $em->getClassMetadata(get_class($entity));
            //$config = $this->listener->getConfiguration($em, $meta->name);
            //$closureMeta = $em->getClassMetadata($config['closure']);
            
            
        }
    }
    
    public function insertNode(EntityManager $em, $entity)
    {
        $meta = $em->getClassMetadata(get_class($entity));
        $config = $this->listener->getConfiguration($em, $meta->name);
        $identifier = $meta->getSingleIdentifierFieldName();
        $id = $meta->getReflectionProperty($identifier)->getValue($entity);
        $closureMeta = $em->getClassMetadata($config['closure']);
        $entries = array();
        $entries[] = array(
        	'ancestor' => $id,
            'descendant' => $id,
            'depth' => 0
        );

        $parent = $meta->getReflectionProperty($config['parent'])->getValue($entity);
        if ($parent) {
            $parentId = $meta->getReflectionProperty($identifier)->getValue($parent);
            $dql = "SELECT c.ancestor, c.depth FROM {$closureMeta->name} c";
            $dql .= " WHERE c.descendant = {$parentId}";
            $ancestors = $em->createQuery($dql)->getArrayResult();
            //echo count($ancestors);
            foreach ($ancestors as $ancestor) {
                $entries[] = array(
                	'ancestor' => $ancestor['ancestor'],
                    'descendant' => $id,
                    'depth' => $ancestor['depth'] + 1
                );
            }
        }
        
        $table = $closureMeta->getTableName();
        foreach ($entries as $closure) {
            if (!$em->getConnection()->insert($table, $closure)) {
                throw new \Gedmo\Exception\RuntimeException('Failed to insert new Closure record');
            }
        }
    }
    
    private function extractIdentifier($em, $entity, $single = true)
    {
        if ($entity instanceof Proxy) {
            $id = $em->getUnitOfWork()->getEntityIdentifier($entity);
        } else {
            $meta = $em->getClassMetadata(get_class($entity));
            $id = array();
            foreach ($meta->identifier as $name) {
                $id[$name] = $meta->getReflectionProperty($name)->getValue($entity);
            }
        }
        if ($single) {
            $id = current($id);
        }
        return $id;
    }
    
    /**
     * {@inheritdoc}
     */
    public function processScheduledDelete($em, $entity)
    {}
    
    /**
     * {@inheritdoc}
     */
    public function onFlushEnd($em)
    {}
}