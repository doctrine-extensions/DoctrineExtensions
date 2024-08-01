<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Translatable\Fixture\Document\TranslationCollection;

use Doctrine\DBAL\Types\Types;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Translatable\Document\MappedSuperclass\AbstractTranslation;
use Gedmo\Translatable\Document\Repository\TranslationRepository;

/**
 * Gedmo\Translatable\Document\Translation
 *
 * @ODM\Document(
 *     collection="ext_translations",
 *     repositoryClass="Gedmo\Translatable\Document\Repository\TranslationRepository"
 * )
 * @ODM\UniqueIndex(name="lookup_unique_idx", keys={
 *     "locale": "asc",
 *     "object_class": "asc",
 *     "foreign_key": "asc",
 *     "field": "asc"
 * })
 * @ODM\Index(name="translations_lookup_idx", keys={
 *     "locale": "asc",
 *     "object_class": "asc",
 *     "foreign_key": "asc"
 * })
 */
#[ODM\Document(collection: 'ext_translations', repositoryClass: TranslationRepository::class)]
#[ODM\UniqueIndex(keys: [
    'locale' => 'asc',
    'object_class' => 'asc',
    'foreign_key' => 'asc',
    'field' => 'asc',
], name: 'lookup_unique_idx'
)]
#[ODM\Index(keys: [
    'locale' => 'asc',
    'object_class' => 'asc',
    'foreign_key' => 'asc',
], name: 'translations_lookup_idx'
)]
class PersonTranslation extends AbstractTranslation
{
    /**
     * @ODM\Field(
     *     name="full_name",
     *     type= Types::STRING,
     *     nullable= true,
     *     notSaved= true,
     * )
     */
    #[ODM\Field(
        name: 'full_name',
        type: Types::STRING,
        nullable: true,
        notSaved: true,
    )]
    protected ?string $fullName = null;

    public function getFullName(): ?string
    {
        return $this->fullName;
    }
}
