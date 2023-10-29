<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tree\Document\MongoDB\Repository;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Query\Query;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Tree\RepositoryInterface;
use Gedmo\Tree\RepositoryUtils;
use Gedmo\Tree\RepositoryUtilsInterface;
use Gedmo\Tree\TreeListener;

/**
 * @template T of object
 *
 * @phpstan-extends DocumentRepository<T>
 *
 * @phpstan-implements RepositoryInterface<T>
 */
abstract class AbstractTreeRepository extends DocumentRepository implements RepositoryInterface
{
    /**
     * Tree listener on event manager
     *
     * @var TreeListener
     */
    protected $listener;

    /**
     * Repository utils
     *
     * @var RepositoryUtilsInterface
     */
    protected $repoUtils;

    /** @param ClassMetadata<T> $class */
    public function __construct(DocumentManager $em, UnitOfWork $uow, ClassMetadata $class)
    {
        parent::__construct($em, $uow, $class);
        $treeListener = null;
        foreach ($em->getEventManager()->getAllListeners() as $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof TreeListener) {
                    $treeListener = $listener;

                    break 2;
                }
            }
        }

        if (null === $treeListener) {
            throw new InvalidMappingException('This repository can be attached only to ODM MongoDB tree listener');
        }

        $this->listener = $treeListener;
        if (!$this->validate()) {
            throw new InvalidMappingException('This repository cannot be used for tree type: '.$treeListener->getStrategy($em, $class->getName())->getName());
        }

        $this->repoUtils = new RepositoryUtils($this->dm, $this->getClassMetadata(), $this->listener, $this);
    }

    /**
     * Sets the RepositoryUtilsInterface instance
     *
     * @return $this
     */
    public function setRepoUtils(RepositoryUtilsInterface $repoUtils)
    {
        $this->repoUtils = $repoUtils;

        return $this;
    }

    /**
     * Returns the RepositoryUtilsInterface instance
     *
     * @return RepositoryUtilsInterface|null
     */
    public function getRepoUtils()
    {
        return $this->repoUtils;
    }

    public function childrenHierarchy($node = null, $direct = false, array $options = [], $includeNode = false)
    {
        return $this->repoUtils->childrenHierarchy($node, $direct, $options, $includeNode);
    }

    public function buildTree(array $nodes, array $options = [])
    {
        return $this->repoUtils->buildTree($nodes, $options);
    }

    /**
     * @see \Gedmo\Tree\RepositoryUtilsInterface::setChildrenIndex
     */
    public function setChildrenIndex($childrenIndex)
    {
        $this->repoUtils->setChildrenIndex($childrenIndex);
    }

    /**
     * @see \Gedmo\Tree\RepositoryUtilsInterface::getChildrenIndex
     */
    public function getChildrenIndex()
    {
        return $this->repoUtils->getChildrenIndex();
    }

    public function buildTreeArray(array $nodes)
    {
        return $this->repoUtils->buildTreeArray($nodes);
    }

    /**
     * Get all root nodes query builder
     *
     * @param string|null $sortByField Sort by field
     * @param string      $direction   Sort direction ("asc" or "desc")
     *
     * @return Builder
     */
    abstract public function getRootNodesQueryBuilder($sortByField = null, $direction = 'asc');

    /**
     * Get all root nodes query
     *
     * @param string|null $sortByField Sort by field
     * @param string      $direction   Sort direction ("asc" or "desc")
     *
     * @return Query
     */
    abstract public function getRootNodesQuery($sortByField = null, $direction = 'asc');

    /**
     * Returns a QueryBuilder configured to return an array of nodes suitable for buildTree method
     *
     * @param object               $node        Root node
     * @param bool                 $direct      Obtain direct children?
     * @param array<string, mixed> $options     Options
     * @param bool                 $includeNode Include node in results?
     *
     * @return Builder
     */
    abstract public function getNodesHierarchyQueryBuilder($node = null, $direct = false, array $options = [], $includeNode = false);

    /**
     * Returns a Query configured to return an array of nodes suitable for buildTree method
     *
     * @param object               $node        Root node
     * @param bool                 $direct      Obtain direct children?
     * @param array<string, mixed> $options     Options
     * @param bool                 $includeNode Include node in results?
     *
     * @return Query
     */
    abstract public function getNodesHierarchyQuery($node = null, $direct = false, array $options = [], $includeNode = false);

    /**
     * Get list of children followed by given $node. This returns a QueryBuilder object
     *
     * @param object $node        if null, all tree nodes will be taken
     * @param bool   $direct      true to take only direct children
     * @param string $sortByField field name to sort by
     * @param string $direction   sort direction : "ASC" or "DESC"
     * @param bool   $includeNode Include the root node in results?
     *
     * @return Builder
     */
    abstract public function getChildrenQueryBuilder($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false);

    /**
     * Get list of children followed by given $node. This returns a Query
     *
     * @param object $node        if null, all tree nodes will be taken
     * @param bool   $direct      true to take only direct children
     * @param string $sortByField field name to sort by
     * @param string $direction   sort direction : "ASC" or "DESC"
     * @param bool   $includeNode Include the root node in results?
     *
     * @return Query
     */
    abstract public function getChildrenQuery($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false);

    /**
     * Checks if current repository is right
     * for currently used tree strategy
     *
     * @return bool
     */
    abstract protected function validate();
}
