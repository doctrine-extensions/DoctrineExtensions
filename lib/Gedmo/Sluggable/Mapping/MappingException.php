<?php

namespace Gedmo\Sluggable\Mapping;

/**
 * The mapping exception list for Sluggable behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Sluggable.Mapping
 * @subpackage MappingException
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MappingException extends \Exception
{
    static public function fieldMustBeMapped($field, $class)
    {
        return new self("Sluggable: was unable to find [{$field}] as mapped property in entity - {$class}");
    }
    
    static public function noFieldsToSlug($class)
    {
        return new self("Sluggable: was unable to find sluggable fields specified for Sluggable entity - {$class}");
    }
    
    static public function slugFieldIsDuplicate($slugField, $class)
    {
        return new self("Sluggable: there cannot be two slug fields '{$slugField}' in class - {$class}.");
    }
    
    static public function notValidFieldType($field, $class)
    {
        return new self("Sluggable: cannot slug field - [{$field}] type is not valid and must be 'string' in class - {$class}");
    }
}