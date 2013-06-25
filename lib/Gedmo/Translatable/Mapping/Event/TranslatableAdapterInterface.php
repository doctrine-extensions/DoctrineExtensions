<?php

namespace Gedmo\Translatable\Mapping\Event;

/**
 * Doctrine event adapter interface for Translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface TranslatableAdapterInterface
{
    /**
     * Find collection of translations for an object if mapped
     *
     * @param Object $object
     * @param string $translationClass
     * @return null or PersistentCollection
     */
    public function getTranslationCollection($object, $translationClass);

    /**
     * Find translation in given $locale
     *
     * @param Object $object
     * @param string $locale
     * @param string $translationClass
     * @return null or AbstractTranslation
     */
    public function findTranslation($object, $locale, $translationClass);
}
