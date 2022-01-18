<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Translator;

/**
 * Object for managing translations.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
interface TranslationInterface
{
    /**
     * Set the translatable item.
     *
     * @param object $translatable
     *
     * @return void
     */
    public function setTranslatable($translatable);

    /**
     * Get the translatable item.
     *
     * @return object
     */
    public function getTranslatable();

    /**
     * Set the translation locale.
     *
     * @param string $locale
     *
     * @return void
     */
    public function setLocale($locale);

    /**
     * Get the translation locale.
     *
     * @return string
     */
    public function getLocale();

    /**
     * Set the translated property.
     *
     * @param string $property
     *
     * @return void
     */
    public function setProperty($property);

    /**
     * Get the translated property.
     *
     * @return string
     */
    public function getProperty();

    /**
     * Set the translation value.
     *
     * @param string $value
     *
     * @return static
     */
    public function setValue($value);

    /**
     * Get the translation value.
     *
     * @return string
     */
    public function getValue();
}
