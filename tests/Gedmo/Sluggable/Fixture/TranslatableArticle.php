<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Fixture;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sluggable\Sluggable;
use Gedmo\Translatable\Translatable;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class TranslatableArticle implements Sluggable, Translatable
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
     * @ORM\Column(type="string", length=64)
     */
    #[ORM\Column(type: Types::STRING, length: 64)]
    #[Gedmo\Translatable]
    private $title;

    /**
     * @var string|null
     *
     * @Gedmo\Translatable
     * @ORM\Column(type="string", length=16)
     */
    #[ORM\Column(type: Types::STRING, length: 16)]
    #[Gedmo\Translatable]
    private $code;

    /**
     * @var string|null
     *
     * @Gedmo\Translatable
     * @Gedmo\Slug(fields={"title", "code"})
     * @ORM\Column(type="string", length=128)
     */
    #[ORM\Column(type: Types::STRING, length: 128)]
    #[Gedmo\Translatable]
    #[Gedmo\Slug(fields: ['title', 'code'])]
    private $slug;

    /**
     * @var Collection<int, Comment>
     *
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="article")
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'article')]
    private $comments;

    /**
     * @var Page|null
     *
     * @ORM\ManyToOne(targetEntity="Page", inversedBy="articles")
     */
    #[ORM\ManyToOne(targetEntity: Page::class, inversedBy: 'articles')]
    private $page;

    /**
     * @var string|null
     *
     * @Gedmo\Locale
     * Used locale to override Translation listener`s locale
     */
    #[Gedmo\Language]
    private $locale;

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

    public function setPage(?Page $page): void
    {
        $this->page = $page;
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

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setTranslatableLocale(?string $locale): void
    {
        $this->locale = $locale;
    }
}
