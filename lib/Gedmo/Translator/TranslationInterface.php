<?php

namespace Gedmo\Translator;

interface TranslationInterface
{
    /**
     * Set translatable
     *
     * @param string $translatable
     */
    function setTranslatable($translatable);

    /**
     * Get translatable
     *
     * @return string $translatable
     */
    function getTranslatable();

    /**
     * Set locale
     *
     * @param string $locale
     */
    function setLocale($locale);

    /**
     * Get locale
     *
     * @return string $locale
     */
    function getLocale();

    /**
     * Set property
     *
     * @param string $field
     */
    function setProperty($property);

    /**
     * Get property
     *
     * @return string $field
     */
    function getProperty();

    /**
     * Set value
     *
     * @param text $value
     * @return AbstractTranslation
     */
    function setValue($value);

    /**
     * Get value
     *
     * @return text $value
     */
    function getValue();
}
