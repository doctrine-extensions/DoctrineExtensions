<?php

namespace Gedmo\Translator;

/**
 * Object for managing translations.
 *
 * @author  Konstantin Kudryashov <ever.zet@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface TranslationInterface
{
    /**
     * Set the translatable item.
     *
     * @param object $translatable
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
