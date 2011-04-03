<?php

namespace Gedmo\Translatable\Mapping\Event;

/**
 * Doctrine event adapter interface
 * for Translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo\Translatable\Mapping\Event
 * @subpackage TranslatableAdapter
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface TranslatableAdapter
{
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
     * @return array
     */
    function loadTranslations($object, $translationClass, $locale);

    /**
     * Search for existing translation record
     *
     * @param mixed $objectId
     * @param string $objectClass
     * @param string $locale
     * @param string $field
     * @param string $translationClass
     * @return mixed - null if nothing is found, Translation otherwise
     */
    function findTranslation($objectId, $objectClass, $locale, $field, $translationClass);

    /**
     * Removes all associated translations for given object
     *
     * @param mixed $objectId
     * @param string $transClass
     * @return void
     */
    function removeAssociatedTranslations($objectId, $transClass);

    /**
     * Inserts the translation record
     *
     * @param object $translation
     * @return void
     */
    function insertTranslationRecord($translation);
}