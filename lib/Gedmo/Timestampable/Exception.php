<?php

namespace Gedmo\Timestampable;

/**
 * The exception list for Timestampable behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Timestampable
 * @subpackage Exception
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Exception extends \Exception
{
    static public function objectExpected($field, $class)
    {
        return new self("Timestampable: field - [{$field}] is expected to be object in class - {$class}");
    }
}