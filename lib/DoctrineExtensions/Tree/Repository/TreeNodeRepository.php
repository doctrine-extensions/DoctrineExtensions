<?php

namespace DoctrineExtensions\Tree\Repository;

use Doctrine\ORM\EntityRepository,
    Doctrine\ORM\Query,
    DoctrineExtensions\Tree\Node;

/**
 * The TreeNodeRepository has some useful functions
 * to interact with tree.
 * 
 * Some Tree logic is copied from -
 * CakePHP: Rapid Development Framework (http://cakephp.org)
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package DoctrineExtensions.Tree.Repository
 * @subpackage TreeNodeRepository
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TreeNodeRepository extends EntityRepository
{   
	/**
	 * Get the Tree path of Nodes by given $node
	 * 
	 * @param Node $node
	 * @return array - list of Nodes in path
	 */
	public function getPath(Node $node)
	{
		$result = array();
		$meta = $this->_em->getClassMetadata($this->_entityName);
    	$config = $node->getTreeConfiguration();
		
    	$left = $meta->getReflectionProperty($config->getLeftField())
    		->getValue($node);
    	$right = $meta->getReflectionProperty($config->getRightField())
    		->getValue($node);
    	if (!empty($left) && !empty($right)) {
    		$qb = $this->_em->createQueryBuilder();
	    	$qb->select('node')
	    		->from($this->_entityName, 'node')
	    		->where('node.' . $config->getLeftField() . " <= :left")
	    		->andWhere('node.' . $config->getRightField() . " >= :right")
	    		->orderBy('node.' . $config->getLeftField(), 'ASC');
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
	 * @param Node $node - if null counts all records in tree
	 * @param boolean $direct - true to count only direct children
	 * @return integer
	 */ 
    public function childCount($node = null, $direct = false)
    {
    	$count = 0;
    	$meta = $this->_em->getClassMetadata($this->_entityName);
    	$nodeId = $meta->getSingleIdentifierFieldName();
    	if ($node instanceof Node) {
	    	$config = $node->getTreeConfiguration();
	    	if ($direct) {
	    		$id = $meta->getReflectionProperty($nodeId)->getValue($node);
	    		$qb = $this->_em->createQueryBuilder();
	    		$qb->select('COUNT(node.' . $nodeId . ')')
	    			->from($this->_entityName, 'node')
	    			->where('node.' . $config->getParentField() . ' = ' . $id);
	    			
	    		$q = $qb->getQuery();
	    		$count = intval($q->getSingleScalarResult());
	    	} else {
	    		$left = $meta->getReflectionProperty($config->getLeftField())
	    			->getValue($node);
	    		$right = $meta->getReflectionProperty($config->getRightField())
	    			->getValue($node);
	    		if (!empty($left) && !empty($right)) {
	    			$count = ($right - $left - 1) / 2;
	    		}
	    	}
    	} else {
    		$q = $this->_em->createQuery("SELECT COUNT(node.{$nodeId}) FROM {$this->_entityName} node");
    		$count = intval($q->getSingleScalarResult());
    	}
    	return $count;
    }
    
    /**
     * Get list of children followed by given $node
     * 
     * @param Node $node - if null, all tree nodes will be taken
     * @param boolean $direct - true to take only direct children
     * @return array - list of given $node children, null on failure
     */
    public function children($node = null, $direct = false)
    {
    	$meta = $this->_em->getClassMetadata($this->_entityName);
    	$qb = $this->_em->createQueryBuilder();
	    $qb->select('node')
	    	->from($this->_entityName, 'node');
    	if ($node instanceof Node) {
    		$config = $node->getTreeConfiguration();
    		if ($direct) {
    			$nodeId = $meta->getSingleIdentifierFieldName();
    			$id = $meta->getReflectionProperty($nodeId)->getValue($node);
	    		$qb->where('node.' . $config->getParentField() . ' = ' . $id);
	    	} else {
	    		$left = $meta->getReflectionProperty($config->getLeftField())
	    			->getValue($node);
	    		$right = $meta->getReflectionProperty($config->getRightField())
	    			->getValue($node);
	    		if (!empty($left) && !empty($right)) {
	    			$qb->where('node.' . $config->getRightField() . " < {$right}")
	    				->andWhere('node.' . $config->getLeftField() . " > {$left}");
	    		}
	    	}
    	} else {
    		$node = new $this->_entityName();
    		$config = $node->getTreeConfiguration();
    		if ($direct) {
    			$qb->where('node.' . $config->getParentField() . ' IS NULL');
    		}
    	}
    	$qb->orderBy('node.' . $config->getLeftField(), 'ASC');
	    $q = $qb->getQuery();
	    return $q->getResult(Query::HYDRATE_OBJECT);
    }
}
