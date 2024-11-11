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
 * @ODM\Document
 *
 * @Gedmo\Revisionable(revisionClass="Gedmo\Tests\Revisionable\Fixture\Document\CommentRevision")
 */
#[ODM\Document]
#[Gedmo\Revisionable(revisionClass: CommentRevision::class)]
class Comment implements Revisionable
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
    private ?string $subject = null;

    /**
     * @ODM\Field(type="string")
     *
     * @Gedmo\Versioned
     */
    #[ODM\Field(type: Type::STRING)]
    #[Gedmo\Versioned]
    private ?string $message = null;

    /**
     * @ODM\Field(type="date_immutable")
     *
     * @Gedmo\Versioned
     */
    #[ODM\Field(type: Type::DATE_IMMUTABLE)]
    #[Gedmo\Versioned]
    private ?\DateTimeImmutable $writtenAt = null;

    /**
     * @ODM\ReferenceOne(targetDocument="Gedmo\Tests\Revisionable\Fixture\Document\RelatedArticle", inversedBy="comments")
     *
     * @Gedmo\Versioned
     */
    #[ODM\ReferenceOne(targetDocument: RelatedArticle::class, inversedBy: 'comments')]
    #[Gedmo\Versioned]
    private ?RelatedArticle $article = null;

    /**
     * @ODM\EmbedOne(targetDocument="Gedmo\Tests\Revisionable\Fixture\Document\Author")
     *
     * @Gedmo\Versioned
     */
    #[ODM\EmbedOne(targetDocument: Author::class)]
    #[Gedmo\Versioned]
    private ?Author $author = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setSubject(?string $subject): void
    {
        $this->subject = $subject;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setMessage(?string $message): void
    {
        $this->message = $message;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setWrittenAt(?\DateTimeImmutable $writtenAt): void
    {
        $this->writtenAt = $writtenAt;
    }

    public function getWrittenAt(): ?\DateTimeImmutable
    {
        return $this->writtenAt;
    }

    public function setArticle(?RelatedArticle $article): void
    {
        $this->article = $article;
    }

    public function getArticle(): ?RelatedArticle
    {
        return $this->article;
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
