<?php

namespace Gedmo\Translatable\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;
use Doctrine\ODM\MongoDB\Mapping\Annotations\UniqueIndex;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Index;

/**
 * Gedmo\Translatable\Document\Translation
 *
 * @Document(repositoryClass="Gedmo\Translatable\Document\Repository\TranslationRepository")
 * @UniqueIndex(name="lookup_unique_idx", keys={
 *         "locale",
 *         "object_class",
 *         "foreign_key",
 *         "field"
 * })
 * @Index(name="translations_lookup_idx", keys={
 *      "locale",
 *      "object_class",
 *      "foreign_key"
 * })
 */
class Translation extends AbstractTranslation
{
    /**
     * All required columns are mapped through inherited superclass
     */
}