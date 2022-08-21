<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Timestampable\Fixture\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type as MongoDBType;

/**
 * @ODM\Document(collection="books")
 */
#[ODM\Document(collection: 'books')]
class Book
{
    /**
     * @ODM\Id
     *
     * @var string
     */
    #[ODM\Id]
    protected $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    #[ODM\Field(type: MongoDBType::STRING)]
    protected $title;

    /**
     * @ODM\EmbedMany(targetDocument="Gedmo\Tests\Timestampable\Fixture\Document\Tag")
     *
     * @var Collection<int, Tag>
     */
    #[ODM\EmbedMany(targetDocument: Tag::class)]
    protected $tags;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param Collection<int, Tag> $tags
     */
    public function setTags(Collection $tags): void
    {
        $this->tags = $tags;
    }

    public function addTag(Tag $tag): void
    {
        $this->tags->add($tag);
    }
}
