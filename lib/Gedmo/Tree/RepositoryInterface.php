<?php

namespace Gedmo\Tree;

/**
 * This interface ensures a consisten api between repositories for the ORM and the ODM.
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree
 * @subpackage RepositoryInterface
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface RepositoryInterface
{
    /**
     * Get all root nodes query builder
     *
     * @return object - QueryBuilder object
     */
    public function getRootNodesQueryBuilder($sortByField = null, $direction = 'asc');

    /**
     * Get all root nodes query
     *
     * @return object - Query object
     */
    public function getRootNodesQuery($sortByField = null, $direction = 'asc');

    /**
     * Get all root nodes
     *
     * @return array
     */
    public function getRootNodes($sortByField = null, $direction = 'asc');

    /**
     * Returns a QueryBuilder configured to return an array of nodes suitable for buildTree method
     *
     * @param object $node - Root node
     * @param bool $direct - Obtain direct children?
     * @param array $config - Metadata configuration
     * @param array $options - Options
     * @param boolean $includeNode - Include node in results?
     *
     * @return object - QueryBuilder object
     */
    public function getNodesHierarchyQueryBuilder($node = null, $direct, array $config, array $options = array(), $includeNode = false);

    /**
     * Returns a Query configured to return an array of nodes suitable for buildTree method
     *
     * @param object $node - Root node
     * @param bool $direct - Obtain direct children?
     * @param array $config - Metadata configuration
     * @param array $options - Options
     * @param boolean $includeNode - Include node in results?
     *
     * @return object - Query object
     */
    public function getNodesHierarchyQuery($node = null, $direct, array $config, array $options = array(), $includeNode = false);

    /**
     * Returns an array of nodes suitable for method buildTree
     *
     * @param object $node - Root node
     * @param bool $direct - Obtain direct children?
     * @param array $config - Metadata configuration
     * @param array $options - Options
     * @param boolean $includeNode - Include node in results?
     *
     * @return array - Array of nodes
     */
    public function getNodesHierarchy($node = null, $direct, array $config, array $options = array(), $includeNode = false);

    /**
     * Get list of children followed by given $node. This returns a QueryBuilder object
     *
     * @param object $node - if null, all tree nodes will be taken
     * @param boolean $direct - true to take only direct children
     * @param string $sortByField - field name to sort by
     * @param string $direction - sort direction : "ASC" or "DESC"
     * @param bool $includeNode - Include the root node in results?
     * @return object - QueryBuilder object
     */
    public function getChildrenQueryBuilder($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false);

    /**
     * Get list of children followed by given $node. This returns a Query
     *
     * @param object $node - if null, all tree nodes will be taken
     * @param boolean $direct - true to take only direct children
     * @param string $sortByField - field name to sort by
     * @param string $direction - sort direction : "ASC" or "DESC"
     * @param bool $includeNode - Include the root node in results?
     * @return object - Query object
     */
    public function getChildrenQuery($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false);

    /**
     * Get list of children followed by given $node
     *
     * @param object $node - if null, all tree nodes will be taken
     * @param boolean $direct - true to take only direct children
     * @param string $sortByField - field name to sort by
     * @param string $direction - sort direction : "ASC" or "DESC"
     * @param bool $includeNode - Include the root node in results?
     * @return array - list of given $node children, null on failure
     */
    public function getChildren($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false);

    /**
     * @see \Gedmo\Tree\RepositoryUtilsInterface::childrenHierarchy
     */
    public function childrenHierarchy($node = null, $direct = false, array $options = array());

    /**
     * @see \Gedmo\Tree\RepositoryUtilsInterface::buildTree
     */
    public function buildTree(array $nodes, array $options = array());

    /**
     * @see \Gedmo\Tree\RepositoryUtilsInterface::buildTreeArray
     */
    public function buildTreeArray(array $nodes);
}
