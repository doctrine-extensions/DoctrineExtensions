<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Translatable\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation;
use Gedmo\Translatable\Entity\Repository\TranslationRepository;

/**
 * @ORM\Table(
 *     name="ext_translations",
 *     indexes={@ORM\Index(name="translations_lookup_idx", columns={
 *         "locale", "object_class", "foreign_key"
 *     })},
 *     uniqueConstraints={@ORM\UniqueConstraint(name="lookup_unique_idx", columns={
 *         "locale", "object_class", "foreign_key", "field"
 *     })}
 * )
 * @ORM\Entity(repositoryClass="Gedmo\Translatable\Entity\Repository\TranslationRepository")
 */
#[ORM\Entity(repositoryClass: TranslationRepository::class)]
#[ORM\Table(name: 'ext_translations')]
#[ORM\Index(name: 'translations_lookup_idx', columns: ['locale', 'object_Class', 'foreign_key'])]
#[ORM\UniqueConstraint(name: 'lookup_unique_idx', columns: ['locale', 'object_Class', 'foreign_key', 'field'])]
class PersonTranslation extends AbstractTranslation
{
}
