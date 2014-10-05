<?php

namespace Gedmo\Sluggable;

/**
 * This interface is not necessary but can be implemented for
 * Entities which in some cases needs to be identified as
 * Sluggable
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface Sluggable
{
    // use now annotations instead of predefined methods, this interface is not necessary

    /**
     * @gedmo:Sluggable
     * to mark the field as sluggable use property annotation @gedmo:Sluggable
     * this field value will be included in built slug
     */

    /**
     * @gedmo:Slug - to mark property which will hold slug use annotation @gedmo:Slug
     * available options:
     *         updatable (optional, default=true) - true to update the slug on sluggable field changes, false - otherwise
     *         unique (optional, default=true) - true if slug should be unique and if identical it will be prefixed, false - otherwise
     *         unique_base (optional, default="") - used in conjunction with unique. The name of the entity property that should be used as a key when doing a uniqueness check
     *         separator (optional, default="-") - separator which will separate words in slug
     *         prefix (optional, default="") - suffix which will be added to the generated slug
     *         suffix (optional, default="") - prefix which will be added to the generated slug
     *         style (optional, default="default") - "default" all letters will be lowercase, "camel" - first word letter will be uppercase
     *         dateFormat (optional, default="default") - "default" all letters will be lowercase, "camel" - first word letter will be uppercase
     *
     * example:
     *
     * @gedmo:Slug(style="camel", separator="_", prefix="", suffix="", updatable=false, unique=false)
     * @Column(type="string", length=64)
     * $property
     */
}
