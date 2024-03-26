<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Translatable;

/**
 * This interface is not necessary but can be implemented for
 * Entities which in some cases needs to be identified as
 * Translatable
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
interface Translatable
{
    // use now annotations instead of predefined methods, this interface is not necessary

    /*
     * @Gedmo\TranslationEntity
     * to specify custom translation class use
     * class annotation @Gedmo\TranslationEntity(class="your\class")
     */

    /*
     * @Gedmo\Translatable
     * to mark the field as translatable,
     * these fields will be translated
     */

    /*
     * @Gedmo\Locale OR @Gedmo\Language
     * to mark the field as locale used to override global
     * locale settings from TranslatableListener
     */
}
