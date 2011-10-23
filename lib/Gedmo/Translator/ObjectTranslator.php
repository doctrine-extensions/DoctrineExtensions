<?php

namespace Gedmo\Translator;

use Doctrine\Common\Collections\Collection;

/**
 * TranslationsCollection
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 * @package Gedmo.Translator
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ObjectTranslator
{
    private $translatable;
    private $properties;
    private $class;
    private $coll;
    private $defaultValues = array();
    private $currentLocale;

    /**
     * Initializes translations collection
     *
     * @param   Object      $translatable   object to translate
     * @param   array       $properties     object properties to translate
     * @param   string      $class          translation entity|document class
     * @param   Collection  $coll           translations collection
     */
    public function __construct($translatable, array $properties, $class, Collection $coll)
    {
        $this->translatable = $translatable;
        $this->properties   = $properties;
        $this->class        = $class;
        $this->coll         = $coll;

        $translationClass = new \ReflectionClass($class);
        if (!$translationClass->implementsInterface('Gedmo\Translator\TranslationInterface')) {
            throw new \InvalidArgumentException(sprintf(
                'Translation class should implement Gedmo\Translator\TranslationInterface, "%s" given',
                $class
            ));
        }

        $translatableClass = new \ReflectionObject($translatable);
        foreach ($properties as $property) {
            $translatableProperty = $translatableClass->getProperty($property);
            $translatableProperty->setAccessible(true);
            $this->defaultValues[$property] = $translatableProperty->getValue($translatable);
        }
    }

    /**
     * Change translatable properties of translatable entity|document to localized ones
     *
     * @param   string  $locale     locale (null === default)
     */
    public function translate($locale = null)
    {
        $locale = null !== $locale ? strtolower($locale) : null;

        if ($locale === $this->currentLocale) {
            return $this->translatable;
        }

        $translatableClass = new \ReflectionObject($this->translatable);
        // iterate over translatable properties
        foreach ($this->properties as $property) {
            $translatableProperty = $translatableClass->getProperty($property);
            $translatableProperty->setAccessible(true);

            $value = $translatableProperty->getValue($this->translatable);

            // save current locale value
            if (null === $this->currentLocale) {
                $this->defaultValues[$property] = $value;
            } else {
                $translation = $this->getTranslationForProperty($property, $this->currentLocale);
                $translation->setValue($value);
            }

            // load new locale value
            if (null === $locale) {
                $value = $this->defaultValues[$property];
            } else {
                $translation = $this->getOrCreateTranslationForProperty($property, $locale);
                $value = $translation->getValue();
            }

            $translatableProperty->setValue($this->translatable, $value);
        }

        $this->currentLocale = $locale;

        return $this->translatable;
    }

    /**
     * Finds or creates new translation for specified property
     *
     * @param   string  $property   object property name
     * @param   string  $locale     locale name
     *
     * @return  Translation
     */
    public function getOrCreateTranslationForProperty($property, $locale)
    {
        if (!($translation = $this->getTranslationForProperty($property, $locale))) {
            $translation = new $this->class;
            $translation->setTranslatable($this->translatable);
            $translation->setProperty($property);
            $translation->setLocale($locale);
            $this->coll->add($translation);
        }

        return $translation;
    }

    /**
     * Finds translation for specified property
     *
     * @param   string  $property   object property name
     * @param   string  $locale     locale name
     *
     * @return  Translation
     */
    public function getTranslationForProperty($property, $locale)
    {
        foreach ($this->coll as $translation) {
            if ($locale === $translation->getLocale() && $property === $translation->getProperty()) {
                return $translation;
            }
        }
    }
}
