<?php

namespace Translator\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translator\Entity\Translation;

/**
 * @ORM\Table(
 *         indexes={@ORM\Index(name="translations_lookup_idx", columns={
 *             "locale", "translatable_id"
 *         })},
 *         uniqueConstraints={@ORM\UniqueConstraint(name="lookup_unique_idx", columns={
 *             "locale", "translatable_id", "property"
 *         })}
 * )
 * @ORM\Entity
 */
class PersonTranslation extends Translation
{
    /**
     * @ORM\ManyToOne(targetEntity="Person", inversedBy="translations")
     */
    protected $translatable;
}
