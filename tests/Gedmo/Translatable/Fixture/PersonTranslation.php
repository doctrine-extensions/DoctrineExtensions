<?php

namespace Translatable\Fixture;

use Doctrine\ORM\Mapping as ORM;

use Gedmo\Translatable\Entity\AbstractTranslation;

/**
 * @ORM\Table(
 *         name="ext_translations",
 *         indexes={@ORM\index(name="translations_lookup_idx", columns={
 *             "locale", "object_class", "foreign_key"
 *         })},
 *         uniqueConstraints={@ORM\UniqueConstraint(name="lookup_unique_idx", columns={
 *             "locale", "object_class", "foreign_key", "field"
 *         })}
 * )
 * @ORM\Entity(repositoryClass="Gedmo\Translatable\Entity\Repository\TranslationRepository")
 */
class PersonTranslation extends AbstractTranslation
{

}