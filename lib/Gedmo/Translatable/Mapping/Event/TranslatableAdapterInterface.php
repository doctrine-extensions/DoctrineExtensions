<?php

namespace Gedmo\Translatable\Mapping\Event;

use Doctrine\Common\Collections\Collection;
use Gedmo\Translatable\Document\MappedSuperclass\AbstractTranslation as AbstractTranslationDocument;
use Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation as AbstractTranslationEntity;

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
     * @param object $object
     * @param string $translationClass
     *
     * @return Collection|null
     */
    public function getTranslationCollection($object, $translationClass);

    /**
     * Find translation in given $locale
     *
     * @param object $object
     * @param string $locale
     * @param string $translationClass
     *
     * @return AbstractTranslationEntity|AbstractTranslationDocument|null
     */
    public function findTranslation($object, $locale, $translationClass);
}
