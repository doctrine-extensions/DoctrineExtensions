<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tree;

use Gedmo\Exception\InvalidArgumentException;

interface RepositoryUtilsInterface
{
    /**
     * Retrieves the nested array or decorated output.
     *
     * Uses options to handle decorations
     *
     * @param object|null $node        The object to fetch children for; if null, all nodes will be retrieved
     * @param bool        $direct      Flag indicating whether only direct children should be retrieved
     * @param array       $options     Options configuring the output, supported keys include:
     *                                 - decorate: boolean (false) - retrieves the tree as an HTML `<ul>` element
     *                                 - nodeDecorator: Closure (null) - uses $node as argument and returns the decorated item as a string
     *                                 - rootOpen: string || Closure ('<ul>') - branch start, Closure will be given $children as a parameter
     *                                 - rootClose: string ('</ul>') - branch close
     *                                 - childOpen: string || Closure ('<li>') - start of node, Closure will be given $node as a parameter
     *                                 - childClose: string ('</li>') - close of node
     *                                 - childSort: array || keys allowed: field: field to sort on, dir: direction. 'asc' or 'desc'
     * @param bool        $includeNode Flag indicating whether the given node should be included in the results
     *
     * @return array|string
     *
     * @throws InvalidArgumentException
     */
    public function childrenHierarchy($node = null, $direct = false, array $options = [], $includeNode = false);

    /**
     * Retrieves the nested array or the decorated output.
     *
     * Uses options to handle decorations
     *
     * NOTE: nodes should be fetched and hydrated as array
     *
     * @param object[] $nodes   The nodes to build the tree from
     * @param array    $options Options configuring the output, supported keys include:
     *                          - decorate: boolean (false) - retrieves the tree as an HTML `<ul>` element
     *                          - nodeDecorator: Closure (null) - uses $node as argument and returns the decorated item as a string
     *                          - rootOpen: string || Closure ('<ul>') - branch start, Closure will be given $children as a parameter
     *                          - rootClose: string ('</ul>') - branch close
     *                          - childOpen: string || Closure ('<li>') - start of node, Closure will be given $node as a parameter
     *                          - childClose: string ('</li>') - close of node
     *
     * @return array|string
     *
     * @throws InvalidArgumentException
     */
    public function buildTree(array $nodes, array $options = []);

    /**
     * Process a list of nodes and produce an array with the structure of the tree.
     *
     * @param object[] $nodes The nodes to build the tree from
     *
     * @return array
     */
    public function buildTreeArray(array $nodes);

    /**
     * Sets the current children index.
     *
     * @param string $childrenIndex
     *
     * @return void
     */
    public function setChildrenIndex($childrenIndex);

    /**
     * Gets the current children index.
     *
     * @return string
     */
    public function getChildrenIndex();
}
