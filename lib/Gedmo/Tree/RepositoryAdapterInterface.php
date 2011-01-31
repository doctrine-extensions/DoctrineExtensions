<?php

namespace Gedmo\Tree;

interface RepositoryAdapterInterface
{
    /**
     * Get the tree path of given node
     * 
     * @param object $node
     * @return array - list of nodes in path
     */
    function getNodePathInTree($node);
    
    /**
     * Counts the children of given node
     * 
     * @param object $node - if null counts all records in tree
     * @param boolean $direct - true to count only direct children
     * @return integer
     */ 
    function countChildren($node, $direct);
    
    /**
     * Get list of children followed by given $node
     * 
     * @param object $node - if null, all tree nodes will be taken
     * @param boolean $direct - true to take only direct children
     * @param string $sortByField - field name to sort by
     * @param string $direction - sort direction : "ASC" or "DESC"
     * @return array - list of given $node children, null on failure
     */
    function getChildren($node, $direct, $sortByField, $direction);
    
    /**
     * Get list of leaf nodes of the tree
     *
     * @param string $sortByField - field name to sort by
     * @param string $direction - sort direction : "ASC" or "DESC"
     * @return array - list of given $node children, null on failure
     */
    function getTreeLeafs($sortByField, $direction);
    
    /**
     * Move the node down in the same level
     * 
     * @param object $node
     * @param mixed $number
     *         integer - number of positions to shift
     *         boolean - true shift till last position
     * @return boolean - true if shifted
     */
    function moveNodeDown($node, $number);
    
    /**
     * Move the node up in the same level
     * 
     * @param object $node
     * @param mixed $number
     *         integer - number of positions to shift
     *         boolean - true shift till first position
     * @return boolean - true if shifted
     */
    function moveNodeUp($node, $number);
    
    /**
     * Reorders the sibling nodes and child nodes by given $node,
     * according to the $sortByField and $direction specified
     * 
     * @param object $node - null to reorder all tree
     * @param string $sortByField - field name to sort by
     * @param string $direction - sort direction : "ASC" or "DESC"
     * @param boolean $verify - true to verify tree first
     * @return boolean - true on success
     */
    function reorder($node, $sortByField, $direction, $verify);
    
    /**
     * Removes given $node from the tree and reparents its descendants
     * 
     * @param object $node
     * @return void
     */
    function removeNodeFromTree($node);
    
    /**
     * Verifies that current tree is valid.
     * If any error is detected it will return an array
     * with a list of errors found on tree
     * 
     * @return mixed
     *         boolean - true on success
     *         array - error list on failure
     */
    function verifyTreeConsistence();
    
    /**
     * Tries to recover the tree
     * 
     * @return void
     */
    function recoverTree();
}