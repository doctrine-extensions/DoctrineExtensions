<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Loggable\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;
use Gedmo\Loggable\Loggable;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tests\Loggable\Fixture\Document\Log\Comment as CommentLog;

/**
 * @ODM\Document
 * @Gedmo\Loggable(logEntryClass="Gedmo\Tests\Loggable\Fixture\Document\Log\Comment")
 */
#[ODM\Document]
#[Gedmo\Loggable(logEntryClass: CommentLog::class)]
class Comment implements Loggable
{
    /**
     * @var string|null
     * @ODM\Id
     */
    #[ODM\Id]
    private $id;

    /**
     * @var string|null
     * @Gedmo\Versioned
     * @ODM\Field(type="string")
     */
    #[ODM\Field(type: Type::STRING)]
    #[Gedmo\Versioned]
    private $subject;

    /**
     * @var string|null
     * @Gedmo\Versioned
     * @ODM\Field(type="string")
     */
    #[ODM\Field(type: Type::STRING)]
    #[Gedmo\Versioned]
    private $message;

    /**
     * @var RelatedArticle|null
     * @Gedmo\Versioned
     * @ODM\ReferenceOne(targetDocument="Gedmo\Tests\Loggable\Fixture\Document\RelatedArticle", inversedBy="comments")
     */
    #[ODM\ReferenceOne(targetDocument: RelatedArticle::class, inversedBy: 'comments')]
    #[Gedmo\Versioned]
    private $article;

    /**
     * @var Author|null
     * @ODM\EmbedOne(targetDocument="Gedmo\Tests\Loggable\Fixture\Document\Author")
     * @Gedmo\Versioned
     */
    #[ODM\EmbedOne(targetDocument: Author::class)]
    #[Gedmo\Versioned]
    private $author;

    public function setArticle(?RelatedArticle $article): void
    {
        $this->article = $article;
    }

    public function getArticle(): ?RelatedArticle
    {
        return $this->article;
    }

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

    public function setAuthor(?Author $author): void
    {
        $this->author = $author;
    }

    public function getAuthor(): ?Author
    {
        return $this->author;
    }
}
