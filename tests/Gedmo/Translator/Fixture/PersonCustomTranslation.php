<?php

namespace Translator\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translator\Entity\Translation;

/**
 * @ORM\Table(
 *         indexes={@ORM\Index(name="pers_translations_lookup_idx", columns={
 *             "locale", "translatable_id"
 *         })},
 *         uniqueConstraints={@ORM\UniqueConstraint(name="pers_lookup_unique_idx", columns={
 *             "locale", "translatable_id", "property"
 *         })}
 * )
 * @ORM\Entity
 */
class PersonCustomTranslation extends Translation
{
    /**
     * @ORM\ManyToOne(targetEntity="PersonCustom", inversedBy="translations")
     */
    protected $translatable;
}
