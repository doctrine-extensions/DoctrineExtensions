<?php

namespace Gedmo\Sluggable;

/**
 * The exception list for Sluggable behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Sluggable
 * @subpackage Exception
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Exception extends \Exception
{
    static public function slugIsEmpty()
    {
        return new self("Sluggable: was unable to find any non empty sluggable fields, make sure they have something at least.");
    }
    
    static public function slugFieldIsUnique($slugField)
    {
        return new self("Sluggable: cannot support unique slug field '{$slugField}' during concurent updates, make an index on it.");
    }
}