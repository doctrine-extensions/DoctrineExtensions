<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Sluggable;

/**
 * Marker interface for objects which can be identified as sluggable.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
interface Sluggable
{
    // use now annotations instead of predefined methods, this interface is not necessary

    /*
     * @Gedmo\Sluggable
     * to mark the field as sluggable use property annotation @Gedmo\Sluggable
     * this field value will be included in built slug
     */

    /*
     * @Gedmo\Slug - to mark property which will hold slug use annotation @Gedmo\Slug
     * available options:
     *         updatable (optional, default=true) - true to update the slug on sluggable field changes, false - otherwise
     *         unique (optional, default=true) - true if slug should be unique and if identical it will be prefixed, false - otherwise
     *         unique_base (optional, default="") - used in conjunction with unique. The name of the entity property that should be used as a key when doing a uniqueness check
     *         separator (optional, default="-") - separator which will separate words in slug
     *         prefix (optional, default="") - prefix which will be added to the generated slug
     *         suffix (optional, default="") - suffix which will be added to the generated slug
     *         style (optional, default="default") - "default" all letters will be lowercase, "camel" - first word letter will be uppercase
     *         dateFormat (optional, default="default") - "default" all letters will be lowercase, "camel" - first word letter will be uppercase
     *         uniqueOverTranslations (optional, default=false) - true if slug should be unique over translations and if identical it will be prefixed, false - otherwise
     *
     * example:
     *
     * @Gedmo\Slug(style="camel", separator="_", prefix="", suffix="", updatable=false, unique=false)
     * @Column(type="string", length=64)
     * $property
     */
}
