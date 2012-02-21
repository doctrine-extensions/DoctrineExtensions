<?php

namespace Gedmo\Tree\Entity\Repository;

use Gedmo\Exception\InvalidArgumentException,
    Gedmo\Tree\Strategy,
    Gedmo\Tree\Strategy\ORM\MaterializedPath,
    Gedmo\Tool\Wrapper\EntityWrapper;

/**
 * The MaterializedPathRepository has some useful functions
 * to interact with MaterializedPath tree. Repository uses
 * the strategy used by listener
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree.Entity.Repository
 * @subpackage MaterializedPathRepository
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MaterializedPathRepository extends AbstractTreeRepository
{
    /**
     * Get tree query builder
     *
     * @return Doctrine\ORM\QueryBuilder
     */
    public function getTreeQueryBuilder()
    {
        return $this->getChildrenQueryBuilder();
    }

    /**
     * Get tree query
     *
     * @return Doctrine\ORM\Query
     */
    public function getTreeQuery()
    {
        return $this->getTreeQueryBuilder()->getQuery();
    }

    /**
     * Get tree
     *
     * @return array
     */
    public function getTree()
    {
        return $this->getTreeQuery()->execute();
    }

    /**
     * Get all root nodes query builder
     *
     * @return Doctrine\ORM\QueryBuilder
     */
    public function getRootNodesQueryBuilder($sortByField = null, $direction = 'asc')
    {
        return $this->getChildrenQueryBuilder(null, true, $sortByField, $direction);
    }

    /**
     * Get all root nodes query
     *
     * @return Doctrine\ORM\Query
     */
    public function getRootNodesQuery($sortByField = null, $direction = 'asc')
    {
        return $this->getRootNodesQueryBuilder($sortByField, $direction)->getQuery();
    }

    /**
     * Get all root nodes
     *
     * @return array
     */
    public function getRootNodes($sortByField = null, $direction = 'asc')
    {
        return $this->getRootNodesQuery($sortByField, $direction)->execute();
    }

    /**
     * Get children from node
     *
     * @return Doctrine\ORM\QueryBuilder
     */
    public function getChildrenQueryBuilder($node = null, $direct = false, $sortByField = null, $direction = 'asc')
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->_em, $meta->name);
        $separator = addcslashes($config['path_separator'], '%');
        $alias = 'materialized_path_entity';
        $path = $config['path'];
        $qb = $this->_em->createQueryBuilder($meta->name)
            ->select($alias)
            ->from($meta->name, $alias);

        if (is_object($node) && $node instanceof $meta->name) {
            $node = new EntityWrapper($node, $this->_em);
            $nodePath = $node->getPropertyValue($path);
            $expr = $qb->expr()->andx()->add(
                $qb->expr()->like($alias.'.'.$path, $qb->expr()->literal($nodePath.'%'))
            );
            $expr->add($qb->expr()->neq($alias.'.'.$path, $qb->expr()->literal($nodePath)));

            if ($direct) {
                $expr->add(
                    $qb->expr()->not(
                        $qb->expr()->like($alias.'.'.$path, $qb->expr()->literal($nodePath.'%'.$separator.'%'.$separator))
                ));
            }

            $qb->where('('.$expr.')');
        } else if ($direct) {
            $expr = $qb->expr()->not(
                $qb->expr()->like($alias.'.'.$path, $qb->expr()->literal('%'.$separator.'%'.$separator.'%'))
            );
            $qb->where('('.$expr.')');
        }

        $orderByField = is_null($sortByField) ? $alias.'.'.$config['path'] : $alias.'.'.$sortByField;
        $orderByDir = $direction === 'asc' ? 'asc' : 'desc';
        $qb->orderBy($orderByField, $orderByDir);

        return $qb;
    }

    /**
     * Get children query
     *
     * @return Doctrine\ORM\Query
     */
    public function getChildrenQuery($node = null, $direct = false, $sortByField = null, $direction = 'asc')
    {
        return $this->getChildrenQueryBuilder($node, $direct, $sortByField, $direction)->getQuery();
    }

    /**
     * Get children
     *
     * @return array
     */
    public function getChildren($node = null, $direct = false, $sortByField = null, $direction = 'asc')
    {
        return $this->getChildrenQuery($node, $direct, $sortByField, $direction)->execute();
    }

    /**
     * {@inheritdoc}
     */
    protected function validate()
    {
        return $this->listener->getStrategy($this->_em, $this->getClassMetadata()->name)->getName() === Strategy::MATERIALIZED_PATH;
    }
}