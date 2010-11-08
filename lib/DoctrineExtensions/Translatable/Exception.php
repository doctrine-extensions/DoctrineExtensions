<?php

namespace DoctrineExtensions\Translatable;

/**
 * The exception list for Translatable behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package DoctrineExtensions.Translatable
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
    
    static public function failedToInsert()
    {
        return new self("Translatable: failed to insert new Translation record");
    }
    
    static public function translationClassLoaderArgumentInvalid($type)
    {
        return new self("Translatable: invalid argument [{$type}] given for translation class retrieval.");
    }
    
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