<?php

namespace Gedmo\Translatable\Document;

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