<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Blameable\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type as MongoDBType;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\Document(collection="articles")
 */
#[ODM\Document(collection: 'articles')]
class Article
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    #[ODM\Id]
    private $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    #[ODM\Field(type: MongoDBType::STRING)]
    private $title;

    /**
     * @ODM\ReferenceOne(targetDocument="Type")
     *
     * @var Type|null
     */
    #[Odm\ReferenceOne(targetDocument: Type::class)]
    private $type;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     * @Gedmo\Blameable(on="create")
     */
    #[ODM\Field(type: MongoDBType::STRING)]
    #[Gedmo\Blameable(on: 'create')]
    private $created;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     * @Gedmo\Blameable
     */
    #[ODM\Field(type: MongoDBType::STRING)]
    #[Gedmo\Blameable]
    private $updated;

    /**
     * @ODM\ReferenceOne(targetDocument="User")
     * @Gedmo\Blameable(on="create")
     *
     * @var User|null
     */
    #[ODM\ReferenceOne(targetDocument: User::class)]
    #[Gedmo\Blameable(on: 'create')]
    private $creator;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     * @Gedmo\Blameable(on="change", field="type.title", value="Published")
     */
    #[Gedmo\Blameable(on: 'change', field: 'type.title', value: 'Published')]
    #[ODM\Field(type: MongoDBType::STRING)]
    private $published;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getCreated(): ?string
    {
        return $this->created;
    }

    public function getPublished(): ?string
    {
        return $this->published;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function getUpdated(): ?string
    {
        return $this->updated;
    }

    public function setType(Type $type): void
    {
        $this->type = $type;
    }

    public function getType(): ?Type
    {
        return $this->type;
    }

    public function setCreated(?string $created): void
    {
        $this->created = $created;
    }

    public function setPublished(?string $published): void
    {
        $this->published = $published;
    }

    public function setUpdated(?string $updated): void
    {
        $this->updated = $updated;
    }

    public function setCreator(?User $creator): void
    {
        $this->creator = $creator;
    }
}
