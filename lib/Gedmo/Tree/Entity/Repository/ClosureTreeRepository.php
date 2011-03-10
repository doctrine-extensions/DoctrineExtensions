<?php

namespace Gedmo\Tree\Entity\Repository;

use Gedmo\Tree\AbstractTreeRepository,
    Doctrine\ORM\Query,
    Gedmo\Tree\Strategy,
    Gedmo\Tree\Strategy\ORM\Closure,
    Doctrine\ORM\Proxy\Proxy;

/**
 * The ClosureTreeRepository has some useful functions
 * to interact with Closure tree. Repository uses
 * the strategy used by listener
 *
 * @author Gustavo Adrian <comfortablynumb84@gmail.com> 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree.Entity.Repository
 * @subpackage ClosureRepository
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ClosureTreeRepository extends AbstractTreeRepository
{
    /**
     * Counts the children of given TreeNode
     * 
     * @param object $node - if null counts all records in tree
     * @param boolean $direct - true to count only direct children
     * @return integer
     */ 
    public function childCount( $node = null, $direct = false )
    {
        $meta 			= $this->getClassMetadata();
		$qb 			= $this->_em->createQueryBuilder();
		$config 		= $this->listener->getConfiguration( $this->_em, $meta->name );
		$closureMeta	= $this->_em->getClassMetadata( $config[ 'closure' ] );
		$table			= $closureMeta->getTableName();
		$nodeID 		= $meta->getSingleIdentifierFieldName();
		$id				= $meta->getReflectionProperty( $nodeID )->getValue( $node );
		
		$qb->select( 'COUNT( c.id )' )
			->from( $table, 'c' )
			->where( 'c.ancestor = :node_id' );
		
		if ( $direct === true )
		{
			$qb->andWhere( 'c.depth = 1' );
		}
		
		$qb->setParameter( 'node_id', $id );
		
        return $qb->getQuery()->getSingleScalarResult();
    }
	
	/**
     * {@inheritdoc}
     */
    protected function validates()
    {
        return $this->listener->getStrategy($this->_em, $this->getClassMetadata()->name)->getName() === Strategy::CLOSURE;
    }
}
