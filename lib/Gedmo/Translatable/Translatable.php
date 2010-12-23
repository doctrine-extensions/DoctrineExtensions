<?php

namespace Gedmo\Translatable;

/**
 * This interface is not necessary but can be implemented for
 * Entities which in some cases needs to be identified as
 * Translatable
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Translatable
 * @subpackage Translatable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface Translatable
{
    // use now annotations instead of predifined methods, this interface is not necessary
    
    /**
     * @gedmo:TranslationEntity
     * to specify custom translation class use 
     * class annotation @gedmo:TranslationEntity(class="your\class")
     */
    
    /**
     * @gedmo:Translatable
     * to mark the field as translatable, 
     * these fields will be translated
     */
    
    /**
     * @gedmo:Locale OR @gedmo:Language
     * to mark the field as locale used to override global
     * locale settings from TranslationListener
     */
}