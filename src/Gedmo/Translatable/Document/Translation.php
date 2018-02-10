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
 *         "locale" = "asc",
 *         "object_class" = "asc",
 *         "foreign_key" = "asc",
 *         "field" = "asc"
 * })
 * @Index(name="translations_lookup_idx", keys={
 *      "locale" = "asc",
 *      "object_class" = "asc",
 *      "foreign_key" = "asc"
 * })
 */
class Translation extends MappedSuperclass\AbstractTranslation
{
    /**
     * All required columns are mapped through inherited superclass
     */
}
