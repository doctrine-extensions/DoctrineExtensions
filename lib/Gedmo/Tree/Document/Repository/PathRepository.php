<?php

/*
 * This file is part of the DoctrineExtensions library.
 *
 * (c) 2011 Gediminas Morkevifcius
 * (c) Funsational <info@funsational.com>
 *
 * This source file is subject to the LICENSE located in
 * the root directory of this distrubution.
 */

namespace Gedmo\Tree\Document\Repository;

use Gedmo\Tree\Strategy\ODM\Path;
use Gedmo\Tree\Node;
use Gedmo\Tree\Strategy\ODM\Path as PathStrategy;

/**
 * This repository contains common methods for retrieving trees
 * and managing the materialized path tree structure
 *
 * @author Michael Williams <michael.williams@funsational.com>
 */
class PathRepository extends AbstractTreeRepository
{
    /**
     * Increases the sort for all nodes that have a sort field
     * greater than or equal to $startingSort. It will update nodes greater
     * than if $equal is false and nodes greater than or equal to if $equal
     * is true. This method is used primiarly internally for moving and inserting
     * nodes into the tree.
     *
     * @param int       $startingSort
     * @param boolean   $rootNodePath
     * @param int       $increaseBy
     * @param int       $endingSort
     */
    public function increaseSort($startingSort, $equal = false, $increaseBy = 1, $endingSort = 0)
    {
        // @todo Support config values
        $qb = $this->createQueryBuilder();

        if ($equal) {
            $qb->field('sortOrder')->gte($startingSort);
        } else {
            $qb->field('sortOrder')->gt($startingSort);
        }

        if ($equal && $endingSort) {
            $qb->field('sortOrder')->lte($endingSort);
        } else if ($endingSort) {
            $qb->field('sortOrder')->lt($endingSort);
        }

        //->field('path')->equals(new \MongoRegex('/^' . $rootNodePath . ',/i')) @todo For later

        $qb->update()
            ->field('sortOrder')->inc($increaseBy)
        ;

        return $qb->getQuery(array('multiple' => true, 'safe' => true))->execute();
    }

    /**
     * Count the number of descendants for a given $parentPath. This will
     * not count the parent node.
     *
     * @param string $parentPath
     */
    public function countDescendants($parentPath)
    {
        $count = $this->getDescendantsQueryBuilder($parentPath)
            ->getQuery()
            ->count()
        ;

        return $count;
    }

    /**
     * Fetches $limit number of descendants after skipping $skip
     *
     * @param string $parentPath
     * @param string $sortBy
     * @param string $sortDir
     * @param int $limit
     * @param int $skip
     */
    public function fetchDescendants($parentPath, $sortBy, $sortDir = 'desc', $limit = false, $skip = false)
    {
        $qb = $this->getDescendantsQueryBuilder($parentPath);

        if ($limit) {
            $qb->limit($limit);
        }

        if ($skip) {
        	$qb->skip($skip);
        }

        if ($sortBy && $sortDir) {
            $qb->sort($sortBy, $sortDir);
        }

        return $qb->getQuery()->execute();
    }

    public function deleteDescendants($parentPath)
    {
    	$qb = $this->getDescendantsQueryBuilder($parentPath)
            ->remove()
            ->getQuery(array('safe' => true))
            ->execute()
        ;
    }

    /**
     * Gets the query builder for finding a nodes descendants
     *
     * @param string $parentPath
     */
    protected function getDescendantsQueryBuilder($parentPath)
    {
        $qb = $this->createQueryBuilder()
            ->field('path')->equals(new \MongoRegex('/^' . $parentPath . '(.+)/i'))
        ;

        return $qb;
    }

    public function decreaseChildCount(Node $node, $decreaseBy = 1)
    {
        $this->createQueryBuilder()
            ->field('id')->equals(new \MongoId($node->getParent()->getId()))
            ->update()
            ->field('childCount')->inc(-1 * $decreaseBy)
            ->getQuery(array('safe' => true))
            ->execute()
        ;
    }

    /**
     * Finds all ancestors for the given $node
     *
     * @param Node $node
     * @param string $sortBy
     * @param string $sortDir
     */
    public function findAncestors(Node $node, $sortBy = false, $sortDir = false)
    {
    	if (($sortBy && !$sortDir) || (!$sortBy && $sortDir)) {
    		throw new \InvalidArgumentException('You must specifiy both $sortBy and $sortDir');
    	}

    	$meta = $this->getClassMetadata();
    	$ancestorsPaths = $this->listener
    	   ->getStrategy($this->dm, $meta->name)
    	   ->getAncestorsPath($node)
        ;

        $qb = $this->createQueryBuilder()
            ->field('path')->in($ancestorsPaths)
        ;

        if ($sortBy && $sortDir) {
        	$qb->sort($sortBy, $sortDir);
        }

        return $qb->getQuery()->execute();
    }

    /**
     * Increases the $nodes child count by $increasyBy. This
     * also updates the nodes child count directly.
     *
     * @param Gedmo\Tree\Node $node
     * @param int $increaseBy
     */
    public function increaseChildCount(Node $node, $increaseBy = 1)
    {
        // @todo Add support for reading metadata config
        $this->createQueryBuilder()
            ->field('id')->equals(new \MongoId($node->getId()))
            ->update()
            ->field('childCount')->inc($increaseBy)
            ->getQuery(array('safe' => true))
            ->execute()
        ;

        // @todo Update the reflection property for the node
    }

    /**
     * Finds the max sort that is currently stored in the DB.
     *
     * @param boolean $refresh  If set to true, Doctrine will refresh the already
     *                          hydrated object if it has been persisted. For saftey
     *                          it is good to set refresh to true, however you should
     *                          test if you can get away without doing a refresh on
     *                          a case-by-case basis as it helps with performance.
     */
    public function findMaxSort($refresh = false, $path = null)
    {
        $qb = $this->createQueryBuilder()
            ->select('sortOrder')
            ->sort('sortOrder', 'desc')
            ->limit(1)
            ->refresh($refresh)
        ;

        if ($path) {
        	$qb->field('path')->equals(new \MongoRegex('/^' . $path . '(.+)?/i'));
        }

        $node = $qb->getQuery(array('safe' => true))
            ->getSingleResult()
        ;

        if ($node) {
            // @todo Support for different field name
            return $node->getSortOrder();
        }

        return false;
    }

    /**
     * Allows the following 'virtual' methods:
     * - persistAsFirstChild($node)
     * - persistAsFirstChildOf($node, $parent)
     * - persistAsLastChild($node)
     * - persistAsLastChildOf($node, $parent)
     * - persistAsNextSibling($node)
     * - persistAsNextSiblingOf($node, $sibling)
     * - persistAsPrevSibling($node)
     * - persistAsPrevSiblingOf($node, $sibling)
     *
     * Inherited virtual methods:
     * - find*
     *
     * Note that calling persistAsNextSibling($node) is the same as calling
     * persistAsLastChild($node), it will insert it as the very last root node
     * and persistAsPrevSibling($node) is the same as calling persistAsFirstChild($node),
     * it will always insert the node as the very first root node.
     *
     * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
     * @throws InvalidArgumentException - If arguments are invalid
     * @throws BadMethodCallException - If the method called is an invalid find* or persistAs* method
     *      or no find* either persistAs* method at all and therefore an invalid method call.
     * @return mixed - TreeNestedRepository if persistAs* is called
     */
    public function __call($method, $args)
    {
        if (substr($method, 0, 9) === 'persistAs') {
            if (!isset($args[0])) {
                throw new \Gedmo\Exception\InvalidArgumentException('Node to persist must be available as first argument');
            }

            $node = $args[0];
            $meta = $this->getClassMetadata();
            $config = $this->listener->getConfiguration($this->dm, $meta->name);
            $position = substr($method, 9);

            if (substr($method, -2) === 'Of') {
                if (!isset($args[1])) {
                    throw new \Gedmo\Exception\InvalidArgumentException('If "Of" is specified you must provide parent or sibling as the second argument');
                }
                $parent = $args[1];
                $meta->getReflectionProperty($config['parent'])->setValue($node, $parent);
                $position = substr($position, 0, -2);
            }

            $oid = spl_object_hash($node);
            $this->listener
                ->getStrategy($this->dm, $meta->name)
                ->setNodePosition($oid, $position);

            $this->dm->persist($node);

            return $this;
        }

        return parent::__call($method, $args);
    }

    /**
     * Checks if the repository validates for the class we are trying to use it
     * for. It will return true the the class that is using this repository
     * has a mapping for @gedmo:Tree(type="path"), false otherwise.
     */
    protected function validates()
    {
        return $this->listener->getStrategy($this->dm, $this->getClassMetadata()->name)->getName() === PathStrategy::PATH;;
    }
}