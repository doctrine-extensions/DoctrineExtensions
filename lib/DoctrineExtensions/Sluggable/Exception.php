<?php

namespace DoctrineExtensions\Sluggable;

/**
 * The exception list for Sluggable behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package DoctrineExtensions.Sluggable
 * @subpackage Exception
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Exception extends \Exception
{    
    static public function pendingInserts()
    {
        return new self("Sluggable: Unit of work has pending inserts, cannot request query execution");
    }
    
    static public function fieldMustBeMapped($field, $class)
    {
        return new self("Sluggable: was unable to find [{$field}] as mapped property in entity - {$class}");
    }
    
    static public function noFieldsToSlug($class)
    {
        return new self("Sluggable: was unable to find sluggable fields specified for Sluggable entity - {$class}");
    }
    
    static public function invalidSlugType($type)
    {
        return new self("Sluggable: requires slug to be 'string' type '{$type}' is detected.");
    }
    
    static public function invalidSlugLength($real, $prefered)
    {
        return new self("Sluggable: cannot proceed with prefered '{$prefered}' slug length, then field allows '{$real}' length.");
    }
    
    static public function slugFieldIsDuplicate($slugField, $class)
    {
        return new self("Sluggable: there cannot be two slug fields '{$slugField}' in class - {$class}.");
    }
    
    static public function slugFieldIsUnique($slugField)
    {
        return new self("Sluggable: cannot support unique slug field '{$slugField}' during concurent updates, make an index on it.");
    }
    
    static public function slugIsEmpty()
    {
        return new self("Sluggable: was unable to find any non empty sluggable fields, make sure they have something at least.");
    }
    
    static public function notValidFieldType($field, $class)
    {
        return new self("Sluggable: cannot slug field - [{$field}] type is not valid and must be 'string' in class - {$class}");
    }
}