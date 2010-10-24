<?php

namespace DoctrineExtensions\Tree;

/**
 * This interface must be implemented for all entities
 * to activate the Tree behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package DoctrineExtensions.Tree
 * @subpackage Node
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface Node
{
	/**
     * Specifies the configuration for tree
     * 
     * @see Tree\Configuration for options available
     * @return Tree\Configuration
     */
    public function getTreeConfiguration();
}