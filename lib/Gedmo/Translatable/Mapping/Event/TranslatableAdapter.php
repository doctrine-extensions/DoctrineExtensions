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
     * @return boolean
     */
    function usesPersonalTranslation($translationClassName);

    /**
     * Get default LogEntry class used to store the logs
     *
     * @return string
     */
    function getDefaultTranslationClass();

    /**
     * Load the translations for a given object
     *
     * @param object $object
     * @param string $translationClass
     * @param string $locale
     * @param string $objectClass
     * @return array
     */
    function loadTranslations($object, $translationClass, $locale, $objectClass);

    /**
     * Search for existing translation record
     *
     * @param AbstractWrapper $wrapped
     * @param string $locale
     * @param string $field
     * @param string $translationClass
     * @param string $objectClass
     * @return mixed - null if nothing is found, Translation otherwise
     */
    function findTranslation(AbstractWrapper $wrapped, $locale, $field, $translationClass, $objectClass);

    /**
     * Removes all associated translations for given object
     *
     * @param AbstractWrapper $wrapped
     * @param string $transClass
     * @param string $objectClass
     * @return void
     */
    function removeAssociatedTranslations(AbstractWrapper $wrapped, $transClass, $objectClass);

    /**
     * Inserts the translation record
     *
     * @param object $translation
     * @return void
     */
    function insertTranslationRecord($translation);

    /**
     * Get the transformed value for translation
     * storage
     *
     * @param object $object
     * @param string $field
     * @param mixed $value
     * @return mixed
     */
    function getTranslationValue($object, $field, $value = false);

    /**
     * Transform the value from database
     * for translation
     *
     * @param object $object
     * @param string $field
     * @param mixed $value
     */
    function setTranslationValue($object, $field, $value);
}
