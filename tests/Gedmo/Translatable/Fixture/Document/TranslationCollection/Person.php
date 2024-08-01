<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and licence information, please view the LICENCE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Translatable\Fixture\Document\TranslationCollection;

use Doctrine\DBAL\Types\Types;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\Document(collection="persons")
 */
#[ODM\Document(collection: 'persons')]
#[Gedmo\TranslationEntity(class: PersonTranslation::class)]
class Person
{
    /**
     * @var ?string
     * @ODM\Id
     */
    #[ODM\Id]
    private ?string $id = null;

    /**
     * @Gedmo\Translatable
     *
     * @ODM\Field(name="name", type="string")
     */
    #[Gedmo\Translatable]
    #[ODM\Field(name: 'name', type: Types::STRING)]
    private ?string $name = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}