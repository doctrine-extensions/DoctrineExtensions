<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Loggable\Fixture\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Loggable\Loggable;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tests\Loggable\Fixture\Entity\Log\Comment as CommentLog;

/**
 * @ORM\Entity
 * @Gedmo\Loggable(logEntryClass="Gedmo\Tests\Loggable\Fixture\Entity\Log\Comment")
 */
#[ORM\Entity]
#[Gedmo\Loggable(logEntryClass: CommentLog::class)]
class Comment implements Loggable
{
    /**
     * @var int|null
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * @var string|null
     * @Gedmo\Versioned
     * @ORM\Column(length=128)
     */
    #[ORM\Column(length: 128)]
    #[Gedmo\Versioned]
    private $subject;

    /**
     * @var string|null
     * @Gedmo\Versioned
     * @ORM\Column(type="text")
     */
    #[ORM\Column(type: Types::TEXT)]
    #[Gedmo\Versioned]
    private $message;

    /**
     * @var RelatedArticle|null
     * @Gedmo\Versioned
     * @ORM\ManyToOne(targetEntity="RelatedArticle", inversedBy="comments")
     */
    #[ORM\ManyToOne(targetEntity: RelatedArticle::class, inversedBy: 'comments')]
    #[Gedmo\Versioned]
    private $article;

    public function setArticle(?RelatedArticle $article): void
    {
        $this->article = $article;
    }

    public function getArticle(): ?RelatedArticle
    {
        return $this->article;
    }

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
}
