<?php

namespace Gedmo\Tree\Document\MongoDB\Repository;

use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Tree\Strategy;
use Gedmo\Tool\Wrapper\MongoDocumentWrapper;

/**
 * The MaterializedPathRepository has some useful functions
 * to interact with MaterializedPath tree. Repository uses
 * the strategy used by listener
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MaterializedPathRepository extends AbstractTreeRepository
{
    /**
     * Get tree query builder
     *
     * @param object $rootNode
     *
     * @return \Doctrine\ODM\MongoDB\Query\Builder
     */
    public function getTreeQueryBuilder($rootNode = null)
    {
        return $this->getChildrenQueryBuilder($rootNode, false, null, 'asc', true);
    }

    /**
     * Get tree query
     *
     * @param object $rootNode
     *
     * @return \Doctrine\ODM\MongoDB\Query\Query
     */
    public function getTreeQuery($rootNode = null)
    {
        return $this->getTreeQueryBuilder($rootNode)->getQuery();
    }

    /**
     * Get tree
     *
     * @param object $rootNode
     *
     * @return \Doctrine\ODM\MongoDB\Cursor
     */
    public function getTree($rootNode = null)
    {
        return $this->getTreeQuery($rootNode)->execute();
    }

    /**
     * {@inheritDoc}
     */
    public function getRootNodesQueryBuilder($sortByField = null, $direction = 'asc')
    {
        return $this->getChildrenQueryBuilder(null, true, $sortByField, $direction);
    }

    /**
     * {@inheritDoc}
     */
    public function getRootNodesQuery($sortByField = null, $direction = 'asc')
    {
        return $this->getRootNodesQueryBuilder($sortByField, $direction)->getQuery();
    }

    /**
     * {@inheritDoc}
     */
    public function getRootNodes($sortByField = null, $direction = 'asc')
    {
        return $this->getRootNodesQuery($sortByField, $direction)->execute();
    }

    /**
     * {@inheritDoc}
     */
    public function childCount($node = null, $direct = false)
    {
        $meta = $this->getClassMetadata();

        if (is_object($node)) {
            if (!($node instanceof $meta->name)) {
                throw new InvalidArgumentException("Node is not related to this repository");
            }

            $wrapped = new MongoDocumentWrapper($node, $this->dm);

            if (!$wrapped->hasValidIdentifier()) {
                throw new InvalidArgumentException("Node is not managed by UnitOfWork");
            }
        }

        $qb = $this->getChildrenQueryBuilder($node, $direct);

        $qb->count();

        return (int) $qb->getQuery()->execute();
    }

    /**
     * {@inheritDoc}
     */
    public function getChildrenQueryBuilder($node = null, $direct = false, $sortByField = null, $direction = 'asc', $includeNode = false)
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->dm, $meta->name);
        $separator = preg_quote($config['path_separator']);
        $qb = $this->dm->createQueryBuilder()
            ->find($meta->name);
        $regex = false;

        if (is_object($node) && $node instanceof $meta->name) {
            $node = new MongoDocumentWrapper($node, $this->dm);
            $nodePath = preg_quote($node->getPropertyValue($config['path']));

            if ($direct) {
                $regex = sprintf('/^%s([^%s]+%s)'.($includeNode ? '?' : '').'$/',
                     $nodePath,
                     $separator,
                     $separator);
            } else {
                $regex = sprintf('/^%s(.+)'.($includeNode ? '?' : '').'/',
                     $nodePath);
            }
        } elseif ($direct) {
            $regex = sprintf('/^([^%s]+)'.($includeNode ? '?' : '').'%s$/',
                $separator,
                $separator);
        }

        if ($regex) {
            $qb->field($config['path'])->equals(new \MongoRegex($regex));
        }

        $qb->sort(is_null($sortByField) ? $config['path'] : $sortByField, $direction === 'asc' ? 'asc' : 'desc');

        return $qb;
    }

    /**
     * G{@inheritDoc}
     */
    public function getChildrenQuery($node = null, $direct = false, $sortByField = null, $direction = 'asc', $includeNode = false)
    {
        return $this->getChildrenQueryBuilder($node, $direct, $sortByField, $direction, $includeNode)->getQuery();
    }

    /**
     * {@inheritDoc}
     */
    public function getChildren($node = null, $direct = false, $sortByField = null, $direction = 'asc', $includeNode = false)
    {
        return $this->getChildrenQuery($node, $direct, $sortByField, $direction, $includeNode)->execute();
    }

    /**
     * {@inheritDoc}
     */
    public function getNodesHierarchyQueryBuilder($node = null, $direct = false, array $options = array(), $includeNode = false)
    {
        $sortBy = array(
            'field'     => null,
            'dir'       => 'asc',
        );

        if (isset($options['childSort'])) {
            $sortBy = array_merge($sortBy, $options['childSort']);
        }

        return $this->getChildrenQueryBuilder($node, $direct, $sortBy['field'], $sortBy['dir'], $includeNode);
    }

    /**
     * {@inheritDoc}
     */
    public function getNodesHierarchyQuery($node = null, $direct = false, array $options = array(), $includeNode = false)
    {
        return $this->getNodesHierarchyQueryBuilder($node, $direct, $options, $includeNode)->getQuery();
    }

    /**
     * {@inheritDoc}
     */
    public function getNodesHierarchy($node = null, $direct = false, array $options = array(), $includeNode = false)
    {
        $query = $this->getNodesHierarchyQuery($node, $direct, $options, $includeNode);
        $query->setHydrate(false);

        return $query->toArray();
    }

    /**
     * {@inheritdoc}
     */
    protected function validate()
    {
        return $this->listener->getStrategy($this->dm, $this->getClassMetadata()->name)->getName() === Strategy::MATERIALIZED_PATH;
    }
}
