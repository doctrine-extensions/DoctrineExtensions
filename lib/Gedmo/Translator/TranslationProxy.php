<?php

namespace Gedmo\Translator;

use Doctrine\Common\Collections\Collection;

/**
 * Proxy class for Entity/Document translations.
 *
 * @author  Konstantin Kudryashov <ever.zet@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslationProxy
{
    protected $locale;
    protected $translatable;
    protected $properties = array();
    protected $class;
    /**
     * @var Collection|TranslationInterface[]
     */
    protected $coll;

    /**
     * Initializes translations collection
     *
     * @param object     $translatable object to translate
     * @param string     $locale       translation name
     * @param array      $properties   object properties to translate
     * @param string     $class        translation entity|document class
     * @param Collection $coll         translations collection
     *
     * @throws \InvalidArgumentException Translation class doesn't implement TranslationInterface
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
            $property = lcfirst($matches[2]);

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
            if (method_exists($this, $getter = 'get'.ucfirst($property))) {
                return $this->$getter;
            }

            return $this->getTranslatedValue($property);
        }

        return $this->translatable->$property;
    }

    public function __set($property, $value)
    {
        if (in_array($property, $this->properties)) {
            if (method_exists($this, $setter = 'set'.ucfirst($property))) {
                return $this->$setter($value);
            }

            return $this->setTranslatedValue($property, $value);
        }

        $this->translatable->$property = $value;
    }

    public function __isset($property)
    {
        return in_array($property, $this->properties);
    }

    /**
     * Returns locale name for the current translation proxy instance.
     *
     * @return string
     */
    public function getProxyLocale()
    {
        return $this->locale;
    }

    /**
     * Returns translated value for specific property.
     *
     * @param string $property property name
     *
     * @return mixed
     */
    public function getTranslatedValue($property)
    {
        return $this
            ->findOrCreateTranslationForProperty($property, $this->getProxyLocale())
            ->getValue();
    }

    /**
     * Sets translated value for specific property.
     *
     * @param string $property property name
     * @param string $value    value
     */
    public function setTranslatedValue($property, $value)
    {
        $this
            ->findOrCreateTranslationForProperty($property, $this->getProxyLocale())
            ->setValue($value);
    }

    /**
     * Finds existing or creates new translation for specified property
     *
     * @param string $property object property name
     * @param string $locale   locale name
     *
     * @return Translation
     */
    private function findOrCreateTranslationForProperty($property, $locale)
    {
        foreach ($this->coll as $translation) {
            if ($locale === $translation->getLocale() && $property === $translation->getProperty()) {
                return $translation;
            }
        }

        /** @var TranslationInterface $translation */
        $translation = new $this->class;
        $translation->setTranslatable($this->translatable);
        $translation->setProperty($property);
        $translation->setLocale($locale);
        $this->coll->add($translation);

        return $translation;
    }
}
