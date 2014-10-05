<?php

namespace Gedmo\Translatable\Mapping\Event;

use Gedmo\Mapping\Event\AdapterInterface;
use Gedmo\Tool\Wrapper\AbstractWrapper;

/**
 * Doctrine event adapter interface
 * for Translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface TranslatableAdapter extends AdapterInterface
{
    /**
     * Checks if $translationClassName is a subclass
     * of personal translation
     *
     * @param string $translationClassName
     *
     * @return boolean
     */
    public function usesPersonalTranslation($translationClassName);

    /**
     * Get default LogEntry class used to store the logs
     *
     * @return string
     */
    public function getDefaultTranslationClass();

    /**
     * Load the translations for a given object
     *
     * @param object $object
     * @param string $translationClass
     * @param string $locale
     * @param string $objectClass
     *
     * @return array
     */
    public function loadTranslations($object, $translationClass, $locale, $objectClass);

    /**
     * Search for existing translation record
     *
     * @param AbstractWrapper $wrapped
     * @param string          $locale
     * @param string          $field
     * @param string          $translationClass
     * @param string          $objectClass
     *
     * @return mixed - null if nothing is found, Translation otherwise
     */
    public function findTranslation(AbstractWrapper $wrapped, $locale, $field, $translationClass, $objectClass);

    /**
     * Removes all associated translations for given object
     *
     * @param AbstractWrapper $wrapped
     * @param string          $transClass
     * @param string          $objectClass
     */
    public function removeAssociatedTranslations(AbstractWrapper $wrapped, $transClass, $objectClass);

    /**
     * Inserts the translation record
     *
     * @param object $translation
     */
    public function insertTranslationRecord($translation);

    /**
     * Get the transformed value for translation
     * storage
     *
     * @param object $object
     * @param string $field
     * @param mixed  $value
     *
     * @return mixed
     */
    public function getTranslationValue($object, $field, $value = false);

    /**
     * Transform the value from database
     * for translation
     *
     * @param object $object
     * @param string $field
     * @param mixed  $value
     */
    public function setTranslationValue($object, $field, $value);
}
