<?php

namespace DoctrineExtensions\Sluggable;

/**
 * This interface is not necessary but can be implemented for
 * Entities which in some cases needs to be identified as
 * Sluggable
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package DoctrineExtensions.Sluggable
 * @subpackage Sluggable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface Sluggable
{
    // use now annotations instead of predifined methods, this interface is not necessary
    
    /**
     * to mark the field as sluggable use property annotation @Sluggable this field will be included in slug
     */
    
    /**
     * to mark property which will hold slug use annotation @Slug
     * available options:
     *         updatable (optional, default=true) - true to update the slug on sluggable field changes, false - otherwise
     *         unique (optional, default=true) - true if slug should be unique and if identical it will be prefixed, false - otherwise
     *         separator (optional, default="-") - separator which will separate words in slug
     *         style (optional, default="default") - "default" all letters will be lowercase, "camel" - first word letter will be uppercase
     * 
     * example:
     * 
     * @Slug(style="camel", separator="_", updatable=false, unique=false)
     * $property
     */
}