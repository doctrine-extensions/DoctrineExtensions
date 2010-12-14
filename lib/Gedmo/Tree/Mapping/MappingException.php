<?php

namespace Gedmo\Tree\Mapping;

/**
 * The mapping exception list for Tree behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree.Mapping
 * @subpackage MappingException
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MappingException extends \Exception
{
    static public function parentFieldNotMappedOrRelated($field, $class)
    {
        return new self("Tree: was unable to find ancestor/parent child relation through ancestor field - [{$field}] in class - {$class}");
    }
    
    static public function missingMetaProperties($fields, $class)
    {
        return new self("Tree: has detected missing properties: " . implode(', ', $fields) . " in class - {$class}");
    }
    
    static public function notValidFieldType($field, $class)
    {
        return new self("Tree: field - [{$field}] type is not valid and must be 'integer', 'smallint' or 'bigint' in class - {$class}");
    }
    
    static public function fieldMustBeMapped($field, $class)
    {
        return new self("Tree: was unable to find [{$field}] as mapped property in entity - {$class}");
    }
}