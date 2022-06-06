<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Translatable\Fixture;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class Article implements Translatable
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    /**
     * @var string|null
     *
     * @Gedmo\Translatable
     * @ORM\Column(name="title", type="string", length=128)
     */
    #[Gedmo\Translatable]
    #[ORM\Column(name: 'title', type: Types::STRING, length: 128)]
    private $title;

    /**
     * @var string|null
     *
     * @Gedmo\Translatable
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    #[Gedmo\Translatable]
    #[ORM\Column(name: 'content', type: Types::TEXT, nullable: true)]
    private $content;

    /**
     * @var int|null
     *
     * @Gedmo\Translatable(fallback=false)
     * @ORM\Column(name="views", type="integer", nullable=true)
     */
    #[Gedmo\Translatable(fallback: false)]
    #[ORM\Column(name: 'views', type: Types::INTEGER, nullable: true)]
    private $views;

    /**
     * @var string|null
     *
     * @Gedmo\Translatable(fallback=true)
     * @ORM\Column(name="author", type="string", nullable=true)
     */
    #[Gedmo\Translatable(fallback: true)]
    #[ORM\Column(name: 'author', type: Types::STRING, nullable: true)]
    private $author;

    /**
     * @var string|null
     *
     * Used locale to override Translation listener`s locale
     *
     * @Gedmo\Locale
     */
    #[Gedmo\Locale]
    private $locale;

    /**
     * @var Collection<int, Comment>
     *
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

    public function setTranslatableLocale(?string $locale): void
    {
        $this->locale = $locale;
    }

    public function setViews(?int $views): void
    {
        $this->views = $views;
    }

    public function getViews(): ?int
    {
        return $this->views;
    }

    public function setAuthor(?string $author): void
    {
        $this->author = $author;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }
}
