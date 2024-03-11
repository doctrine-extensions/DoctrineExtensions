<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Fixture;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
class Page
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 191)]
    private ?string $content = null;

    /**
     * @var string|null
     */
    #[Gedmo\Slug(style: 'camel', separator: '_', fields: ['content'])]
    #[ORM\Column(type: Types::STRING, length: 128)]
    private ?string $slug = null;

    /**
     * @var Collection<int, TranslatableArticle>
     */
    #[ORM\OneToMany(targetEntity: TranslatableArticle::class, mappedBy: 'page')]
    private Collection $articles;

    public function __construct()
    {
        $this->articles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function addArticle(TranslatableArticle $article): void
    {
        $article->setPage($this);
        $this->articles[] = $article;
    }

    /**
     * @return Collection<int, TranslatableArticle>
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function setContent(?string $content): void
    {
        $this->content = $content;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }
}
