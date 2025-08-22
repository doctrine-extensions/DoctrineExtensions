<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Translatable\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation;
use Gedmo\Translatable\Entity\Repository\TranslationRepository;

/**
 * Gedmo\Translatable\Entity\Translation
 *
 * @ORM\Table(
 *     name="ext_translations",
 *     options={"row_format": "DYNAMIC"},
 *     indexes={
 *         @ORM\Index(name="translations_lookup_idx", columns={
 *             "locale", "object_class", "foreign_key"
 *         }),
 *         @ORM\Index(name="general_translations_lookup_idx", columns={
 *             "object_class", "foreign_key"
 *         })
 *     },
 *     uniqueConstraints={@ORM\UniqueConstraint(name="lookup_unique_idx", columns={
 *         "locale", "object_class", "field", "foreign_key"
 *     })}
 * )
 * @ORM\Entity(repositoryClass="Gedmo\Translatable\Entity\Repository\TranslationRepository")
 */
#[ORM\Entity(repositoryClass: TranslationRepository::class)]
#[ORM\Table(name: 'ext_translations', options: ['row_format' => 'DYNAMIC'])]
#[ORM\Index(name: 'translations_lookup_idx', columns: ['locale', 'object_class', 'foreign_key'])]
#[ORM\Index(name: 'general_translations_lookup_idx', columns: ['object_class', 'foreign_key'])]
#[ORM\UniqueConstraint(name: 'lookup_unique_idx', columns: ['locale', 'object_class', 'field', 'foreign_key'])]
class Translation extends AbstractTranslation
{
    /*
     * All required columns are mapped through inherited superclass
     */
}
