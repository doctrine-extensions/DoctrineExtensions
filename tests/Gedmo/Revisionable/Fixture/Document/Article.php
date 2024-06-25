<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Revisionable\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Revisionable\Revisionable;

/**
 * @ODM\Document(collection="articles")
 *
 * @Gedmo\Revisionable
 */
#[ODM\Document(collection: 'articles')]
#[Gedmo\Revisionable]
class Article implements Revisionable
{
    /**
     * @ODM\Id
     */
    #[ODM\Id]
    private ?string $id = null;

    /**
     * @ODM\Field(type="string")
     *
     * @Gedmo\Versioned
     */
    #[ODM\Field(type: Type::STRING)]
    #[Gedmo\Versioned]
    private ?string $title = null;

    /**
     * @ODM\Field(type="date_immutable")
     *
     * @Gedmo\Versioned
     */
    #[ODM\Field(type: Type::DATE_IMMUTABLE)]
    #[Gedmo\Versioned]
    private ?\DateTimeImmutable $publishAt = null;

    /**
     * @ODM\EmbedOne(targetDocument="Gedmo\Tests\Revisionable\Fixture\Document\Author")
     *
     * @Gedmo\Versioned
     */
    #[ODM\EmbedOne(targetDocument: Author::class)]
    #[Gedmo\Versioned]
    private ?Author $author = null;

    public function __toString()
    {
        return $this->title;
    }

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

    public function setPublishAt(?\DateTimeImmutable $publishAt): void
    {
        $this->publishAt = $publishAt;
    }

    public function getPublishAt(): ?\DateTimeImmutable
    {
        return $this->publishAt;
    }

    public function setAuthor(?Author $author): void
    {
        $this->author = $author;
    }

    public function getAuthor(): ?Author
    {
        return $this->author;
    }
}
