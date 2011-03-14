<?php

namespace Gedmo\Tree\Strategy\ORM;

use Gedmo\Tree\Strategy,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\Proxy\Proxy,
    Gedmo\Tree\AbstractTreeListener;

/**
 * This strategy makes tree act like
 * a closure table.
 * 
 * Some Tree logic is copied from -
 * CakePHP: Rapid Development Framework (http://cakephp.org)
 * 
 * @author Gustavo Adrian <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree.Strategy.ORM
 * @subpackage Closure
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
     * List of pending Nodes to remove
     * 
     * @var array
     */
    protected $pendingNodesForRemove = array();
    
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
    public function processPrePersist($em, $entity)
    {
        $this->pendingChildNodeInserts[] = $entity;
    }
    
    /**
     * {@inheritdoc}
     */
    public function processPostPersist($em, $entity)
    {        
        if (count($this->pendingChildNodeInserts)) 
		{
            while ($entity = array_shift($this->pendingChildNodeInserts)) 
            {
                $this->insertNode($em, $entity);
            }
        }
    }
    
    public function insertNode(EntityManager $em, $entity, $addNodeChildrenToAncestors = false)
    {
        $meta = $em->getClassMetadata(get_class($entity));
        $config = $this->listener->getConfiguration($em, $meta->name);
        $identifier = $meta->getSingleIdentifierFieldName();
        $id = $meta->getReflectionProperty($identifier)->getValue($entity);
        $closureMeta = $em->getClassMetadata($config['closure']);
        $entries = array();
		
		// If node has children it means it already has a self referencing row, so we skip its insertion
		if ($addNodeChildrenToAncestors === false) {
            $entries[] = array(
                'ancestor'		=> $id,
                'descendant' 	=> $id,
                'depth' 		=> 0
            );
        }
		
        $parent = $meta->getReflectionProperty($config['parent'])->getValue($entity);
		
        if ( $parent ) {
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
				
                if ($addNodeChildrenToAncestors === true) {
                    $dql 		= "SELECT c.descendant, c.depth FROM {$closureMeta->name} c";
                    $dql 		.= " WHERE c.ancestor = {$id} AND c.ancestor != c.descendant";
                    $children 	= $em->createQuery($dql)
                        ->getArrayResult();
                    
                    foreach ($children as $child)
                    {
                        $entries[] = array(
                            'ancestor'		=> $ancestor['ancestor'],
                            'descendant'	=> $child['descendant'],
                            'depth'			=> $child['depth'] + 1
                        );
                    }
                }
            }	
        }
        
        $table = $closureMeta->getTableName();
        foreach ($entries as $closure) {
            if (!$em->getConnection()->insert($table, $closure)) {
                throw new \Gedmo\Exception\RuntimeException('Failed to insert new Closure record');
            }
        }
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
        $table = $closureMeta->getTableName();
		
        if ($oldParent) 
		{
            $this->removeClosurePathsOfNodeID($em, $table, $nodeId);
            
            $this->insertNode($em, $entity, true);
        }
        
        //\Doctrine\Common\Util\Debug::dump($oldParent);
        //die();
    }
	
	/**
     * {@inheritdoc}
     */
    public function processScheduledDelete($em, $entity)
    {
        $this->removeNode($em, $entity);
    }
	
    public function removeNode(EntityManager $em, $entity, $maintainSelfReferencingRow = false, $maintainSelfReferencingRowOfChildren = false)
	{
        $meta = $em->getClassMetadata(get_class($entity));
        $config = $this->listener->getConfiguration($em, $meta->name);
        $closureMeta = $em->getClassMetadata($config['closure']);
        
        $this->removeClosurePathsOfNodeID($em, $closureMeta->getTableName(), $entity->getId(), $maintainSelfReferencingRow, $maintainSelfReferencingRowOfChildren);
	}
    
	public function removeClosurePathsOfNodeID(EntityManager $em, $table, $nodeId, $maintainSelfReferencingRow = true, $maintainSelfReferencingRowOfChildren = true)
	{
        $subquery = "SELECT c1.id FROM {$table} c1 ";
        $subquery .= "WHERE c1.descendant IN ( SELECT c2.descendant FROM {$table} c2 WHERE c2.ancestor = :id ) ";
        $subquery .= "AND ( c1.ancestor IN ( SELECT c3.ancestor FROM {$table} c3 WHERE c3.descendant = :id ";
        
		if ($maintainSelfReferencingRow === true)
        {
            $subquery .= "AND c3.descendant != c3.ancestor ";
        }
        
        if ( $maintainSelfReferencingRowOfChildren === false )
        {
            $subquery .= " OR c1.descendant = c1.ancestor ";
        }
        
        $subquery .= " ) ) ";
		
        $subquery = "DELETE FROM {$table} WHERE {$table}.id IN ( SELECT temp_table.id FROM ( {$subquery} ) temp_table )";
		
        if (!$em->getConnection()->executeQuery($subquery, array('id' => $nodeId))) {
            throw new \Gedmo\Exception\RuntimeException('Failed to delete old Closure records');
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
    public function onFlushEnd($em)
    {}
}