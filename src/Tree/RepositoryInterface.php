<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tree;

use Gedmo\Exception\InvalidArgumentException;

/**
 * This interface ensures a consistent API between repositories for the ORM and the ODM.
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @template T of object
 */
interface RepositoryInterface extends RepositoryUtilsInterface
{
    /**
     * Get all root nodes.
     *
     * @param string $sortByField
     * @param string $direction
     *
     * @return iterable<int|string, object>
     *
     * @phpstan-return iterable<int|string, T>
     */
    public function getRootNodes($sortByField = null, $direction = 'asc');

    /**
     * Returns an array of nodes optimized for building a tree.
     *
     * @param object               $node        Root node
     * @param bool                 $direct      Flag indicating whether only direct children should be retrieved
     * @param array<string, mixed> $options     Options, see {@see RepositoryUtilsInterface::buildTree()} for supported keys
     * @param bool                 $includeNode Flag indicating whether the given node should be included in the results
     *
     * @return array<int|string, object>
     *
     * @phpstan-param T $node
     *
     * @phpstan-return iterable<int|string, T>
     */
    public function getNodesHierarchy($node = null, $direct = false, array $options = [], $includeNode = false);

    /**
     * Get the list of children for the given node.
     *
     * @param object|null          $node        If null, all tree nodes will be taken
     * @param bool                 $direct      True to take only direct children
     * @param string|string[]|null $sortByField Field name or array of fields names to sort by
     * @param string|string[]      $direction   Sort order ('asc'|'desc'|'ASC'|'DESC'). If $sortByField is an array, this may also be an array with matching number of elements
     * @param bool                 $includeNode Include the root node in results?
     *
     * @return iterable<int|string, object> List of children
     *
     * @phpstan-param 'asc'|'desc'|'ASC'|'DESC'|array<int, 'asc'|'desc'|'ASC'|'DESC'> $direction
     * @phpstan-param T|null $node
     *
     * @phpstan-return iterable<int|string, T>
     */
    public function getChildren($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false);

    /**
     * Counts the children of the given node
     *
     * @param object|null $node   The object to count children for; if null, all nodes will be counted
     * @param bool        $direct Flag indicating whether only direct children should be counted
     *
     * @throws InvalidArgumentException if the input is invalid
     *
     * @return int
     */
    public function childCount($node = null, $direct = false);
}
