<?php

namespace DoctrineExtensions\Translatable;

/**
 * This interface is not necessary but can be implemented for
 * Entities which in some cases needs to be identified as
 * Translatable
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package DoctrineExtensions.Translatable
 * @subpackage Translatable
 * @link http://www.gediminasm.org
 * @version 2.0.0
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface Translatable
{
    // use now annotations instead of predifined methods, this interface is not necessary
    
    // to mark the field as translatable use property annotation @Translatable
    // to specify custom translation class use class annotation @TranslationEntity(class="your\class")
    // to mark the field as locale used to override global use property annotation @Locale or @Language
}