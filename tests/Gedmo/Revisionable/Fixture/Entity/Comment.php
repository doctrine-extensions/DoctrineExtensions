<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Revisionable\Fixture\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Revisionable\Revisionable;

/**
 * @ORM\Entity
 *
 * @Gedmo\Revisionable(revisionClass="Gedmo\Tests\Revisionable\Fixture\Entity\CommentRevision")
 */
#[ORM\Entity]
#[Gedmo\Revisionable(revisionClass: CommentRevision::class)]
class Comment implements Revisionable
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    /**
     * @ORM\Column(name="subject", type="string", length=128)
     *
     * @Gedmo\Versioned
     */
    #[ORM\Column(name: 'subject', type: Types::STRING, length: 128)]
    #[Gedmo\Versioned]
    private ?string $subject = null;

    /**
     * @ORM\Column(name="message", type="text")
     *
     * @Gedmo\Versioned
     */
    #[ORM\Column(name: 'message', type: Types::TEXT)]
    #[Gedmo\Versioned]
    private ?string $message = null;

    /**
     * @ORM\Column(name="written_at", type="datetime_immutable")
     *
     * @Gedmo\Versioned
     */
    #[ORM\Column(name: 'written_at', type: Types::DATETIME_IMMUTABLE)]
    #[Gedmo\Versioned]
    private ?\DateTimeImmutable $writtenAt = null;

    /**
     * @ORM\ManyToOne(targetEntity="Gedmo\Tests\Revisionable\Fixture\Entity\RelatedArticle", inversedBy="comments")
     *
     * @Gedmo\Versioned
     */
    #[ORM\ManyToOne(targetEntity: RelatedArticle::class, inversedBy: 'comments')]
    #[Gedmo\Versioned]
    private ?RelatedArticle $article = null;

    /**
     * @ORM\Embedded(class="Gedmo\Tests\Revisionable\Fixture\Entity\Author")
     *
     * @Gedmo\Versioned
     */
    #[ORM\Embedded(class: Author::class)]
    #[Gedmo\Versioned]
    private ?Author $author = null;

    public function getId(): ?int
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
