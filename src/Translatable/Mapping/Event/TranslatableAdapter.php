<?php

namespace Gedmo\Translatable\Mapping\Event;

use Gedmo\Mapping\Event\AdapterInterface;
use Gedmo\Tool\Wrapper\AbstractWrapper;

/**
 * Doctrine event adapter for the Translatable extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface TranslatableAdapter extends AdapterInterface
{
    /**
     * Checks if the given translation class is a subclass of the personal translation class.
     *
     * @param string $translationClassName
     *
     * @phpstan-param class-string $translationClassName
     *
     * @return bool
     */
    public function usesPersonalTranslation($translationClassName);

    /**
     * Get the default translation class used to store translations.
     *
     * @return string
     * @phpstan-return class-string
     */
    public function getDefaultTranslationClass();

    /**
     * Load the translations for a given object.
     *
     * @param object $object
     * @param string $translationClass
     * @param string $locale
     * @param string $objectClass
     *
     * @phpstan-param class-string $translationClass
     * @phpstan-param class-string $objectClass
     *
     * @return array
     */
    public function loadTranslations($object, $translationClass, $locale, $objectClass);

    /**
     * Search for an existing translation record.
     *
     * @param string $locale
     * @param string $field
     * @param string $translationClass
     * @param string $objectClass
     *
     * @phpstan-param class-string $translationClass
     * @phpstan-param class-string $objectClass
     *
     * @return mixed null if nothing is found, translation object otherwise
     */
    public function findTranslation(AbstractWrapper $wrapped, $locale, $field, $translationClass, $objectClass);

    /**
     * Removes all associated translations for the given object.
     *
     * @param string $transClass
     * @param string $objectClass
     *
     * @phpstan-param class-string $transClass
     * @phpstan-param class-string $objectClass
     */
    public function removeAssociatedTranslations(AbstractWrapper $wrapped, $transClass, $objectClass);

    /**
     * Inserts the translation record.
     *
     * @param object $translation
     */
    public function insertTranslationRecord($translation);

    /**
     * Get the transformed value for translation storage.
     *
     * @param object $object
     * @param string $field
     * @param mixed  $value
     *
     * @return mixed
     */
    public function getTranslationValue($object, $field, $value = false);

    /**
     * Transform the value from the database for translation
     *
     * @param object $object
     * @param string $field
     * @param mixed  $value
     */
    public function setTranslationValue($object, $field, $value);
}
