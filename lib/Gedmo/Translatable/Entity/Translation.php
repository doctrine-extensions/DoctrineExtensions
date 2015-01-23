<?php

namespace Gedmo\Translatable\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Gedmo\Translatable\Entity\Translation
 *
 * @ORM\Table(
 *         name="ext_translations",
 *         indexes={@ORM\Index(name="translations_lookup_idx", columns={
 *             "locale", "object_class", "foreign_key"
 *         })},
 *         uniqueConstraints={@ORM\UniqueConstraint(name="lookup_unique_idx", columns={
 *             "locale", "object_class", "field", "foreign_key"
 *         })}
 * )
 * @ORM\Entity(repositoryClass="Gedmo\Translatable\Entity\Repository\TranslationRepository")
 */
class Translation extends MappedSuperclass\AbstractTranslation
{
    /**
     * All required columns are mapped through inherited superclass
     */
}
