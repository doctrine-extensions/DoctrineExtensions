<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Timestampable\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type as MongoDBType;
use Gedmo\Mapping\Annotation as Gedmo;
use MongoDB\BSON\Timestamp;

/**
 * @ODM\Document(collection="articles")
 */
#[ODM\Document(collection: 'articles')]
class Article
{
    /**
     * @var string|null
     *
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
     * @ODM\ReferenceOne(targetDocument="Gedmo\Tests\Timestampable\Fixture\Document\Type")
     */
    #[ODM\ReferenceOne(targetDocument: Type::class)]
    private $type;

    /**
     * @var int|Timestamp|null
     *
     * @ODM\Field(type="timestamp")
     * @Gedmo\Timestampable(on="create")
     */
    #[Gedmo\Timestampable(on: 'create')]
    #[ODM\Field(type: MongoDBType::TIMESTAMP)]
    private $created;

    /**
     * @var \DateTime|null
     *
     * @ODM\Field(type="date")
     * @Gedmo\Timestampable
     */
    #[Gedmo\Timestampable]
    #[ODM\Field(type: MongoDBType::DATE)]
    private $updated;

    /**
     * @var \DateTime|null
     *
     * @ODM\Field(type="date")
     * @Gedmo\Timestampable(on="change", field="type.title", value="Published")
     */
    #[Gedmo\Timestampable(on: 'change', field: 'type.title', value: 'Published')]
    #[ODM\Field(type: MongoDBType::DATE)]
    private $published;

    /**
     * @var \DateTime|null
     *
     * @ODM\Field(type="date")
     * @Gedmo\Timestampable(on="change", field="isReady", value=true)
     */
    #[Gedmo\Timestampable(on: 'change', field: 'isReady', value: true)]
    #[ODM\Field(type: MongoDBType::DATE)]
    private $ready;

    /**
     * @var bool
     *
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

    /**
     * @return int|Timestamp|null
     */
    public function getCreated()
    {
        return $this->created;
    }

    public function getPublished(): \DateTime
    {
        return $this->published;
    }

    public function getUpdated(): \DateTime
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

    public function setCreated(?int $created): void
    {
        $this->created = $created;
    }

    public function setPublished(\DateTime $published): void
    {
        $this->published = $published;
    }

    public function setUpdated(\DateTime $updated): void
    {
        $this->updated = $updated;
    }

    public function setReady(?\DateTime $ready): self
    {
        $this->ready = $ready;

        return $this;
    }

    public function getReady(): ?\DateTime
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
