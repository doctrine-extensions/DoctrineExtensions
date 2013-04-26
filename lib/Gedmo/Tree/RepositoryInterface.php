<?php

namespace Gedmo\Tree;

/**
 * This interface ensures a consisten api between repositories for the ORM and the ODM.
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface RepositoryInterface extends RepositoryUtilsInterface
{

    /**
     * Get all root nodes
     *
     * @param string $sortByField
     * @param string $direction
     * @return array
     */
    public function getRootNodes($sortByField = null, $direction = 'asc');

    /**
     * Returns an array of nodes suitable for method buildTree
     *
     * @param object $node - Root node
     * @param bool $direct - Obtain direct children?
     * @param array $options - Options
     * @param boolean $includeNode - Include node in results?
     *
     * @return array - Array of nodes
     */
    public function getNodesHierarchy($node = null, $direct = false, array $options = array(), $includeNode = false);

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
     * Counts the children of given TreeNode
     *
     * @param object $node - if null counts all records in tree
     * @param boolean $direct - true to count only direct children
     * @throws \Gedmo\Exception\InvalidArgumentException - if input is not valid
     * @return integer
     */
    public function childCount($node = null, $direct = false);
}
