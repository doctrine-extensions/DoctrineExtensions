<?php

namespace Gedmo\Timestampable\Mapping;

/**
 * The mapping exception list for Timestampable behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Timestampable.Mapping
 * @subpackage MappingException
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MappingException extends \Exception
{
    static public function notValidFieldType($field, $class)
    {
        return new self("Timestampable: field - [{$field}] type is not valid date or time field in class - {$class}");
    }
    
    static public function triggerTypeInvalid($field, $class)
    {
        return new self("Timestampable: field - [{$field}] trigger 'on' is not one of [update, create, change] in class - {$class}");
    }
    
    static public function parametersMissing($field, $class)
    {
        return new self("Timestampable: missing parameters on property - {$field}, field and value must be set on [change] trigger in class - {$class}");
    }
    
    static public function fieldMustBeMapped($field, $class)
    {
        return new self("Timestampable: was unable to find [{$field}] as mapped property in entity - {$class}");
    }
}