<?php

namespace Gedmo\Translatable\Entity;

use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Entity;

/**
 * Gedmo\Translatable\Entity\Translation
 *
 * @Table(name="ext_translations")
 * @Entity(repositoryClass="Gedmo\Translatable\Entity\Repository\TranslationRepository")
 */
class Translation extends MappedSuperclass\AbstractTranslation
{
    /**
     * All required columns are mapped through inherited superclass
     */
}
