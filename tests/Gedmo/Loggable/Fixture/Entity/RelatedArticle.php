<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Loggable\Fixture\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Loggable\Loggable;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @Gedmo\Loggable
 */
#[ORM\Entity]
#[Gedmo\Loggable]
class RelatedArticle implements Loggable
{
    /**
     * @var int|null
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue()]
    private $id;

    /**
     * @var string|null
     * @Gedmo\Versioned
     * @ORM\Column(length=128)
     */
    #[ORM\Column(length: 128)]
    #[Gedmo\Versioned]
    private $title;

    /**
     * @var string|null
     * @Gedmo\Versioned
     * @ORM\Column(type="text")
     */
    #[ORM\Column(Types::TEXT)]
    #[Gedmo\Versioned]
    private $content;

    /**
     * @var Collection<int, Comment>
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="article")
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'article')]
    private $comments;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function addComment(Comment $comment): void
    {
        $comment->setArticle($this);
        $this->comments[] = $comment;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
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
}
