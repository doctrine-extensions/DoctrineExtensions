<?php

namespace Gedmo\Translator;

/**
 * Entity/Document translation interface.
 *
 * @author  Konstantin Kudryashov <ever.zet@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface TranslationInterface
{
    /**
     * Set translatable
     *
     * @param string $translatable
     */
    public function setTranslatable($translatable);

    /**
     * Get translatable
     *
     * @return string
     */
    public function getTranslatable();

    /**
     * Set locale
     *
     * @param string $locale
     */
    public function setLocale($locale);

    /**
     * Get locale
     *
     * @return string
     */
    public function getLocale();

    /**
     * Set property
     *
     * @param string $property
     */
    public function setProperty($property);

    /**
     * Get property
     *
     * @return string
     */
    public function getProperty();

    /**
     * Set value
     *
     * @param string $value
     *
     * @return static
     */
    public function setValue($value);

    /**
     * Get value
     *
     * @return string
     */
    public function getValue();
}
