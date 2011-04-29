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

use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 *
 *
 * @author Michael Williams <michael.williams@funsational.com>
 */
class PathRepository extends DocumentRepository
{
    /**
     * Increases the sort
     * @param $startingSort
     * @param $rootNodePath
     * @param $increaseBy
     */
    public function increaseSort($startingSort, $equal = false, $increaseBy = 1)
    {
    	// @todo Support config values
        $qb = $this->createQueryBuilder();

        if ($equal) {
            $qb->field('sortOrder')->gte($startingSort);
        } else {
            $qb->field('sortOrder')->gt($startingSort);
        }

            //->field('path')->equals(new \MongoRegex('/^' . $rootNodePath . ',/i')) @todo For later
        $qb->update()
            ->field('sortOrder')->inc($increaseBy)
        ;

        return $qb->getQuery(array('multiple' => true))->execute();
    }

    /**
     * Count the number of descendants for a given $parentPath. This will
     * not count the parent node.
     *
     * @param string $parentPath
     */
    public function countDescendants($parentPath)
    {
        $count = $this->createQueryBuilder()
            ->field('path')->equals(new \MongoRegex('/^' . $parentPath . '(.+)/i'))
            ->getQuery()
            ->count()
        ;

        return $count;
    }

    public function increaseChildCount($node, $increaseBy = 1)
    {
    	// @todo Add support for reading metadata config
        $this->createQueryBuilder()
            ->field('id')->equals(new \MongoId($node->getId()))
            ->update()
            ->field('childCount')->inc($increaseBy)
            ->getQuery()
            ->execute()
        ;
    }

    public function findMaxSort()
    {
    	$node = $this->createQueryBuilder()
    	   ->select('sortOrder')
    	   ->sort('sortOrder', 'desc')
    	   ->limit(1)
    	   ->getQuery()
    	   ->getSingleResult()
    	;

    	// @todo Support for different field name
    	return $node->getSortOrder();
    }
}