<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\IpTraceable\Fixture\Document;

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
     * @var string|null
     * @ODM\Id
     */
    #[ODM\Id]
    private $id;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     */
    #[ODM\Field(type: MongoDBType::STRING)]
    private $title;

    /**
     * @var Type|null
     *
     * @ODM\ReferenceOne(targetDocument="Gedmo\Tests\IpTraceable\Fixture\Document\Type")
     */
    #[ODM\ReferenceOne(targetDocument: Type::class)]
    private $type;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     * @Gedmo\IpTraceable(on="create")
     */
    #[ODM\Field(type: MongoDBType::STRING)]
    #[Gedmo\IpTraceable(on: 'create')]
    private $created;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     * @Gedmo\IpTraceable
     */
    #[ODM\Field(type: MongoDBType::STRING)]
    #[Gedmo\IpTraceable]
    private $updated;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     * @Gedmo\IpTraceable(on="change", field="type.title", value="Published")
     */
    #[ODM\Field(type: MongoDBType::STRING)]
    #[Gedmo\IpTraceable(on: 'change', field: 'type.title', value: 'Published')]
    private $published;

    /**
     * @var string|null
     * @ODM\Field(type="string")
     * @Gedmo\IpTraceable(on="change", field="isReady", value=true)
     */
    #[ODM\Field(type: MongoDBType::STRING)]
    #[Gedmo\IpTraceable(on: 'change', field: 'isReady', value: true)]
    private $ready;

    /**
     * @var bool
     * @ODM\Field(type="bool")
     */
    #[ODM\Field(type: MongoDBType::BOOL)]
    private $isReady = false;

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

    public function setReady(?string $ready): self
    {
        $this->ready = $ready;

        return $this;
    }

    public function getReady(): ?string
    {
        return $this->ready;
    }

    public function setIsReady(bool $isReady): self
    {
        $this->isReady = $isReady;

        return $this;
    }

    public function getIsReady(): bool
    {
        return $this->isReady;
    }
}
