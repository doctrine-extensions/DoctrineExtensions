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
    
    /**
     * @Translatable:Entity
     * to specify custom translation class use 
     * class annotation @Translatable:Entity(class="your\class")
     */
    
    /**
     * @Translatable:Field
     * to mark the field as translatable, 
     * these fields will be translated
     */
    
    /**
     * @Translatable:Locale OR @Translatable:Language
     * to mark the field as locale used to override global
     * locale settings from TranslationListener
     */
}