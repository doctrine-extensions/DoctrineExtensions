<?php

namespace Gedmo\Translator;

use Doctrine\Common\Collections\Collection;

class TranslationProxy
{
    protected $locale;
    private $translatable;
    private $properties = array();
    private $class;
    private $coll;

    /**
     * Initializes translations collection
     *
     * @param   Object      $translatable   object to translate
     * @param   string      $locale         translation name
     * @param   array       $properties     object properties to translate
     * @param   string      $class          translation entity|document class
     * @param   Collection  $coll           translations collection
     */
    public function __construct($translatable, $locale, array $properties, $class, Collection $coll)
    {
        $this->translatable = $translatable;
        $this->locale       = $locale;
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
    }

    public function __call($method, $arguments)
    {
        $matches = array();
        if (preg_match('/^(set|get)(.*)$/', $method, $matches)) {
            $property = strtolower($matches[2]);

            if (in_array($property, $this->properties)) {
                switch ($matches[1]) {
                    case 'get':
                        return $this->getTranslatedValue($property);
                    case 'set':
                        if (isset($arguments[0])) {
                            $this->setTranslatedValue($property, $arguments[0]);
                            return $this;
                        }
                }
            }
        }

        $return = call_user_func_array(array($this->translatable, $method), $arguments);

        if ($this->translatable === $return) {
            return $this;
        }

        return $return;
    }

    public function __get($property)
    {
        if (in_array($property, $this->properties)) {
            return $this->getTranslatedValue($property);
        }

        return $this->translatable->$property;
    }

    public function __set($property, $value)
    {
        if (in_array($property, $this->properties)) {
            $this->setTranslatedValue($property, $value);

            return $value;
        }

        $this->translatable->property = $value;
    }

    public function getTranslatedValue($property)
    {
        return $this->getOrCreateTranslationForProperty($property, $this->locale)->getValue();
    }

    public function setTranslatedValue($property, $value)
    {
        $this->getOrCreateTranslationForProperty($property, $this->locale)->setValue($value);
    }

    /**
     * Finds or creates new translation for specified property
     *
     * @param   string  $property   object property name
     * @param   string  $locale     locale name
     *
     * @return  Translation
     */
    private function getOrCreateTranslationForProperty($property, $locale)
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
    private function getTranslationForProperty($property, $locale)
    {
        foreach ($this->coll as $translation) {
            if ($locale === $translation->getLocale() && $property === $translation->getProperty()) {
                return $translation;
            }
        }
    }
}
