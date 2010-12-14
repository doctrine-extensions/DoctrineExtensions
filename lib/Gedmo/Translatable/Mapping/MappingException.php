<?php

namespace Gedmo\Translatable\Mapping;

/**
 * The mapping exception list for Translatable behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Translatable.Mapping
 * @subpackage MappingException
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MappingException extends \Exception
{   
    static public function translationClassNotFound($class)
    {
        return new self("Translatable: the translation entity class: {$class} was not found.");
    }
    
    static public function notValidFieldType($field, $class)
    {
        return new self("Translatable: cannot translate field - [{$field}] type is not valid and must be 'string' or 'text' in class - {$class}");
    }
    
    static public function fieldMustBeMapped($field, $class)
    {
        return new self("Translatable: was unable to find [{$field}] as mapped property in entity - {$class}");
    }
    
    static public function fieldMustNotBeMapped($field, $class)
    {
        return new self("Translatable: field [{$field}] should not be mapped as column property in entity - {$class}, since it makes no sence");
    }
}