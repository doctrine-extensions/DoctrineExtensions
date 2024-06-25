<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Revisionable\Fixture\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Revisionable\Revisionable;

/**
 * @ORM\Entity
 *
 * @Gedmo\Revisionable
 */
#[ORM\Entity]
#[Gedmo\Revisionable]
class RelatedArticle implements Revisionable
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
     * @ORM\Column(name="title", type="string", length=8)
     *
     * @Gedmo\Versioned
     */
    #[ORM\Column(name: 'title', type: Types::STRING, length: 8)]
    #[Gedmo\Versioned]
    private ?string $title = null;

    /**
     * @ORM\Column(name="content", type="text")
     *
     * @Gedmo\Versioned
     */
    #[ORM\Column(name: 'content', type: Types::TEXT)]
    #[Gedmo\Versioned]
    private ?string $content = null;

    /**
     * @var Collection<int, Comment>
     *
     * @ORM\OneToMany(targetEntity="Gedmo\Tests\Revisionable\Fixture\Entity\Comment", mappedBy="article")
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'article')]
    private Collection $comments;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int
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

    public function setContent(?string $content): void
    {
        $this->content = $content;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function addComment(Comment $comment): void
    {
        if (!$this->comments->contains($comment)) {
            $this->comments[] = $comment;
            $comment->setArticle($this);
        }
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }
}
