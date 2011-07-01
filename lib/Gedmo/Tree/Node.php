<?php

namespace Gedmo\Tree;

/**
 * This interface is not necessary but can be implemented for
 * Entities which in some cases needs to be identified as
 * Tree Node
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Michael Williams <michael.williams@funsational.com>
 * @package Gedmo.Tree
 * @subpackage Node
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface Node
{
    // use now annotations instead of predifined methods, this interface is not necessary

    /**
     * @gedmo:TreeLeft
     * to mark the field as "tree left" use property annotation @gedmo:TreeLeft
     * it will use this field to store tree left value
     */

    /**
     * @gedmo:TreeRight
     * to mark the field as "tree right" use property annotation @gedmo:TreeRight
     * it will use this field to store tree right value
     */

    /**
     * @gedmo:TreeParent
     * in every tree there should be link to parent. To identify a relation
     * as parent relation to child use @Tree:Ancestor annotation on the related property
     */

    /**
     * @gedmo:TreeLevel
     * level of node.
     */

	/**
	 * @gedmo:TreePath
	 * The field which holds the path for the node. This is how the tree is built
	 * and children are determined. This is populated from generating a slug of the
	 * @gedmo:TreePathSource field. You should never set this field manually, ie don't
	 * create a set method for this property.
	 */

	/**
	 * @gedmo:TreePathSource
	 * The field which the @gedmo:TreePath field will be generated. This should be the title
	 * or name of the node. Currently reuqired and only used for the path strategy
	 */

	/**
	 * @gedmo:TreeSort
	 * Used for storing the sort order of a node in the tree. Currently only
	 * used and required for the path strategy. You should never set this
	 * method. ie don't make a set method for this property.
	 */
}