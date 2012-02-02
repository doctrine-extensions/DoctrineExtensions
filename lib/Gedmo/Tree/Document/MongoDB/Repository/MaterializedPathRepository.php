<?php

namespace Gedmo\Tree\Document\MongoDB\Repository;

use Gedmo\Exception\InvalidArgumentException,
    Gedmo\Tree\Strategy,
    Gedmo\Tree\Strategy\ODM\MongoDB\MaterializedPath,
    Gedmo\Tool\Wrapper\MongoDocumentWrapper;

/**
 * The MaterializedPathRepository has some useful functions
 * to interact with MaterializedPath tree. Repository uses
 * the strategy used by listener
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree.Document.MongoDB.Repository
 * @subpackage MaterializedPathRepository
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MaterializedPathRepository extends AbstractTreeRepository
{
    /**
     * Get tree query builder
     *
     * @return Doctrine\ODM\MongoDB\QueryBuilder
     */
    public function getTreeQueryBuilder()
    {
        return $this->getChildrenQueryBuilder();
    }

    /**
     * Get tree query
     *
     * @return Doctrine\ODM\MongoDB\Query\Query
     */
    public function getTreeQuery()
    {
        return $this->getTreeQueryBuilder()->getQuery();
    }

    /**
     * Get tree
     *
     * @return Doctrine\ODM\MongoDB\Cursor
     */
    public function getTree()
    {
        return $this->getTreeQuery()->execute();
    }

    /**
     * Get all root nodes query builder
     *
     * @return Doctrine\ODM\MongoDB\QueryBuilder
     */
    public function getRootNodesQueryBuilder($sortByField = null, $direction = 'asc')
    {
        return $this->getChildrenQueryBuilder(null, true, $sortByField, $direction);
    }

    /**
     * Get all root nodes query
     *
     * @return Doctrine\ODM\MongoDB\Query\Query
     */
    public function getRootNodesQuery($sortByField = null, $direction = 'asc')
    {
        return $this->getRootNodesQueryBuilder($sortByField, $direction)->getQuery();
    }

    /**
     * Get all root nodes
     *
     * @return Doctrine\ODM\MongoDB\Cursor
     */
    public function getRootNodes($sortByField = null, $direction = 'asc')
    {
        return $this->getRootNodesQuery($sortByField, $direction)->execute();
    }

    /**
     * Get children from node
     *
     * @return Doctrine\ODM\MongoDB\QueryBuilder
     */
    public function getChildrenQueryBuilder($node = null, $direct = false, $sortByField = null, $direction = 'asc')
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->dm, $meta->name);
        $separator = preg_quote($config['path_separator']);
        $qb = $this->dm->createQueryBuilder()
            ->find($meta->name);

        if (is_object($node) && $node instanceof $meta->name) {
            $node = new MongoDocumentWrapper($node, $this->dm);
            $nodePath = preg_quote($node->getPropertyValue($config['path']));

            if ($direct) {
                $regex = sprintf('/^%s[^%s]+%s$/',
                     $nodePath,
                     $separator,
                     $separator);
                
            } else {
                $regex = sprintf('/^%s.+/',
                     $nodePath);
            }

            $qb->field($config['path'])->equals(new \MongoRegex($regex));
        } else if ($direct) {
            $qb->field($config['path'])->equals(new \MongoRegex(sprintf('/^[^%s]+%s$/',
                $separator,
                $separator)));
        }

        $qb->sort(is_null($sortByField) ? $config['path'] : $sortByField, $direction === 'asc' ? 'asc' : 'desc');

        return $qb;
    }

    /**
     * Get children query
     *
     * @return Doctrine\ODM\MongoDB\Query\Query
     */
    public function getChildrenQuery($node = null, $direct = false, $sortByField = null, $direction = 'asc')
    {
        return $this->getChildrenQueryBuilder($node, $direct, $sortByField, $direction)->getQuery();
    }

    /**
     * Get children
     *
     * @return Doctrine\ODM\MongoDB\Cursor
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
        return $this->listener->getStrategy($this->dm, $this->getClassMetadata()->name)->getName() === Strategy::MATERIALIZED_PATH;
    }
}