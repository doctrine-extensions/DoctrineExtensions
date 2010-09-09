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
        return new self("Unit of work has pending inserts, cannot request query execution");
    }
    
    static public function cannotFindSlugField($slugField)
    {
    	return new self("SluggableListener was unable to find [{$slugField}] in the Sluggable entity");
    }
    
    static public function noFieldsToSlug()
    {
    	return new self("SluggableListener was unable to find sluggable fields specified for Sluggable entity");
    }
    
    static public function cannotFindFieldToSlug($sluggableField)
    {
        return new self("SluggableListener was unable to find field [{$sluggableField}] for slug generation");
    }
    
    static public function invalidSlugType($type)
    {
        return new self("SluggableListener requires slug to be 'string' type '{$type}' is detected.");
    }
    
    static public function invalidSlugLength($real, $prefered)
    {
        return new self("SluggableListener cannot proceed with prefered '{$prefered}' slug length, then field allows '{$real}' length.");
    }
    
    static public function slugFieldIsUnique($slugField)
    {
        return new self("SluggableListener cannot support unique slug field '{$slugField}' yet, make an index on it.");
    }
}