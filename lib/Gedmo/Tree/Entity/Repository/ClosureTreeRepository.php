<?php

namespace Gedmo\Tree\Entity\Repository;

use Doctrine\ORM\Query,
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
     * @param object $node - The node from which we'll count its children
     * @param boolean $direct - true to count only direct children
     * @return integer
     */
    public function childCount($node, $direct = false)
    {
        $meta = $this->getClassMetadata();
        $id    = $this->getIdFromEntity($node);
        $qb = $this->getQueryBuilder();
        $qb->select('COUNT( c.id )')
            ->from($meta->rootEntityName, 'c')
            ->where('c.ancestor = :node_id')
            ->andWhere('c.ancestor != c.descendant');

        if ($direct === true) {
            $qb->andWhere('c.depth = 1');
        }

        $qb->setParameter('node_id', $id);

        return $qb->getQuery()->getSingleScalarResult();
    }


    protected function getQueryBuilder()
    {
        $qb = $this->_em->createQueryBuilder();

        return $qb;
    }

    protected function getIdFromEntity( $node )
    {
        $meta = $this->_em->getClassMetadata(get_class($node));
        $nodeID = $meta->getSingleIdentifierFieldName();
        $refProp = $meta->getReflectionProperty($nodeID);
        $id = $refProp->getValue($node);

        return $id;
    }

    /**
     * {@inheritdoc}
     */
    protected function validates()
    {
        // Temporarily solution to validation problem with this class
        return true;
    }
}
