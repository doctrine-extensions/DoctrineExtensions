<?php

namespace Gedmo\Translatable\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;

/**
 * Gedmo\Translatable\Document\Translation
 *
 * @Document(repositoryClass="Gedmo\Translatable\Document\Repository\TranslationRepository")
 */
class Translation extends MappedSuperclass\AbstractTranslation
{
    /**
     * All required columns are mapped through inherited superclass
     */
}
