<?php

namespace Gedmo\Mapping;

/**
 * The mapping exception list extension driver mapping
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Mapping
 * @subpackage DriverException
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class DriverException extends \Exception
{   
    static public function annotationDriverNotFound($driverClassName)
    {
        return new self("Extension annotation driver: ({$driverClassName}) was not found.");
    }
    
    static public function invalidEntity($className)
    {
        return new self('Class ' . $className . ' is not a valid entity or mapped super class.');
    }
    
    static public function mappingFileNotFound($fileName, $className)
    {
        return new self("No mapping file found named '$fileName' for class '$className'.");
    }
    
    static public function extensionDriverNotSupported($driverClassName, $driverName)
    {
        return new self("Driver: ({$driverName}) currently is not supported for extension metadata parsing. Failed to initialize: {$driverClassName}");
    }
}