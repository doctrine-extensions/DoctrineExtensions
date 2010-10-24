<?php

namespace DoctrineExtensions\Tree;

/**
 * The exception list for Tree behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package DoctrineExtensions.Tree
 * @subpackage Exception
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Exception extends \Exception
{    
    static public function parentFieldNotRelated($field, $class)
    {
        return new self("TreeListener was unable to find parent child relation through parent field - [{$field}] in Node class - {$class}");
    }
    
    static public function cannotFindLeftField($field, $class)
    {
        return new self("TreeListener was unable to find 'left' - [{$field}] in the Node class - {$class}");
    }
    
	static public function cannotFindRightField($field, $class)
    {
        return new self("TreeListener was unable to find 'right' - [{$field}] in the Node class - {$class}");
    }
    
	static public function cannotFindParentField($field, $class)
    {
        return new self("TreeListener was unable to find 'parent' - [{$field}] in the Node class - {$class}");
    }
}