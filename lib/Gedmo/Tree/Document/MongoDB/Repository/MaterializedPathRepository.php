<?php

namespace Gedmo\Tree\Document\MongoDB\Repository;

use Gedmo\Exception\InvalidArgumentException,
    Gedmo\Tree\Strategy,
    Gedmo\Tree\Strategy\ODM\MongoDB\MaterializedPath;

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
     * Get all root nodes query builder
     *
     * @return Doctrine\ODM\MongoDB\QueryBuilder
     */
    public function getRootNodesQueryBuilder()
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->dm, $meta->name);
        $separator = preg_quote($config['path_separator']);
        
        return $this->dm->createQueryBuilder()
            ->find($meta->name)
            ->field($config['path'])->equals(new \MongoRegex(sprintf('/^[^%s]+%s{1}$/u',
                $separator,
                $separator)))
            ->sort($config['path'], 'asc');
    }

    /**
     * Get all root nodes query
     *
     * @return Doctrine\ODM\MongoDB\Query\Query
     */
    public function getRootNodesQuery()
    {
        return $this->getRootNodesQueryBuilder()->getQuery();
    }

    /**
     * Get all root nodes
     *
     * @return Doctrine\ODM\MongoDB\Cursor
     */
    public function getRootNodes()
    {
        return $this->getRootNodesQuery()->execute();
    }

    /**
     * {@inheritdoc}
     */
    protected function validates()
    {
        return $this->listener->getStrategy($this->dm, $this->getClassMetadata()->name)->getName() === Strategy::MATERIALIZED_PATH;
    }
}