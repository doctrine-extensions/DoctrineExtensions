<?php

namespace Gedmo\Translator;

/**
 * Base translation class.
 *
 * @author  Konstantin Kudryashov <ever.zet@gmail.com>
 * @link    http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class Translation implements TranslationInterface
{
    protected $translatable;
    protected $locale;
    protected $property;
    protected $value;

    /**
     * Set translatable
     *
     * @param string $translatable
     */
    public function setTranslatable($translatable)
    {
        $this->translatable = $translatable;
    }

    /**
     * Get translatable
     *
     * @return string $translatable
     */
    public function getTranslatable()
    {
        return $this->translatable;
    }

    /**
     * Set locale
     *
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * Get locale
     *
     * @return string $locale
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set property
     *
     * @param string $field
     */
    public function setProperty($property)
    {
        $this->property = $property;
    }

    /**
     * Get property
     *
     * @return string $field
     */
    public function getProperty()
    {
        return $this->property;
    }

    /**
     * Set value
     *
     * @param text $value
     * @return AbstractTranslation
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Get value
     *
     * @return text $value
     */
    public function getValue()
    {
        return $this->value;
    }
}
