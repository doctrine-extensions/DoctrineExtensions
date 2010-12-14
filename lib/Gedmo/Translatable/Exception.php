<?php

namespace Gedmo\Translatable;

/**
 * The exception list for Translatable behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Translatable
 * @subpackage Exception
 * @link http://www.gediminasm.org
 * @version 2.0.0
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Exception extends \Exception
{
    static public function undefinedLocale()
    {
        return new self("Translatable: locale or language cannot be empty and must be set in Translatable\Listener or in the entity");
    }

    static public function singleIdentifierRequired($entityClass)
    {
        return new self("Translatable: only a single identifier column is required for the Translatable extension, entity: {$entityClass}.");
    }
    
    static public function pendingInserts()
    {
        return new self("Translatable: UnitOfWork has pending inserts, cannot request query execution. TranslationListener does not support Concurrent inserts and updates together, on Doctrine 2 Beta4 yet. Try flushing only inserts or updates");
    }
    
    static public function entityMissingLocaleProperty($field, $className)
    {
        return new self("Translatable: there is no locale field ({$field}) found on entity: {$className}");
    }
    
    static public function failedToInsert()
    {
        return new self("Translatable: failed to insert new Translation record");
    }
}