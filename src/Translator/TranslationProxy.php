<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Translator;

use Doctrine\Common\Collections\Collection;

/**
 * Proxy class for Entity/Document translations.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class TranslationProxy
{
    /**
     * @var string
     */
    protected $locale;
    /**
     * @var object
     */
    protected $translatable;
    /**
     * @var string[]
     */
    protected $properties = [];
    /**
     * @var string
     *
     * @phpstan-var class-string<TranslationInterface>
     */
    protected $class;
    /**
     * @var Collection<int, TranslationInterface>
     */
    protected $coll;

    /**
     * Initializes translations collection
     *
     * @param object   $translatable object to translate
     * @param string   $locale       translation name
     * @param string[] $properties   object properties to translate
     * @param string   $class        translation entity|document class
     *
     * @throws \InvalidArgumentException Translation class doesn't implement TranslationInterface
     *
     * @phpstan-param class-string<TranslationInterface> $class
     * @phpstan-param Collection<int, TranslationInterface> $coll
     */
    public function __construct($translatable, $locale, array $properties, $class, Collection $coll)
    {
        $this->translatable = $translatable;
        $this->locale = $locale;
        $this->properties = $properties;
        $this->class = $class;
        $this->coll = $coll;

        if (!is_subclass_of($class, TranslationInterface::class)) {
            throw new \InvalidArgumentException(sprintf('Translation class should implement %s, "%s" given', TranslationInterface::class, $class));
        }
    }

    /**
     * @param string  $method
     * @param mixed[] $arguments
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        $matches = [];
        if (preg_match('/^(set|get)(.*)$/', $method, $matches)) {
            $property = lcfirst($matches[2]);

            if (in_array($property, $this->properties, true)) {
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

        $return = call_user_func_array([$this->translatable, $method], $arguments);

        if ($this->translatable === $return) {
            return $this;
        }

        return $return;
    }

    /**
     * @param string $property
     *
     * @return mixed
     */
    public function __get($property)
    {
        if (in_array($property, $this->properties, true)) {
            if (method_exists($this, $getter = 'get'.ucfirst($property))) {
                return $this->$getter;
            }

            return $this->getTranslatedValue($property);
        }

        return $this->translatable->$property;
    }

    /**
     * @param string $property
     * @param mixed  $value
     */
    public function __set($property, $value)
    {
        if (in_array($property, $this->properties, true)) {
            if (method_exists($this, $setter = 'set'.ucfirst($property))) {
                $this->$setter($value);

                return;
            }

            $this->setTranslatedValue($property, $value);

            return;
        }

        $this->translatable->$property = $value;
    }

    /**
     * @param string $property
     *
     * @return bool
     */
    public function __isset($property)
    {
        return in_array($property, $this->properties, true);
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
     *
     * @return void
     */
    public function setTranslatedValue($property, $value)
    {
        $this
            ->findOrCreateTranslationForProperty($property, $this->getProxyLocale())
            ->setValue($value);
    }

    /**
     * Finds existing or creates new translation for specified property
     */
    private function findOrCreateTranslationForProperty(string $property, string $locale): TranslationInterface
    {
        foreach ($this->coll as $translation) {
            if ($locale === $translation->getLocale() && $property === $translation->getProperty()) {
                return $translation;
            }
        }

        /** @var TranslationInterface $translation */
        $translation = new $this->class();
        $translation->setTranslatable($this->translatable);
        $translation->setProperty($property);
        $translation->setLocale($locale);
        $this->coll->add($translation);

        return $translation;
    }
}
