<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Fixture\Handler;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sluggable\Handler\RelativeSlugHandler;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class ArticleRelativeSlug
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
     * @ORM\Column(length=64)
     */
    #[ORM\Column(length: 64)]
    private $title;

    /**
     * @var string|null
     *
     * @Gedmo\Slug(handlers={
     *     @Gedmo\SlugHandler(class="Gedmo\Sluggable\Handler\RelativeSlugHandler", options={
     *         @Gedmo\SlugHandlerOption(name="relationField", value="article"),
     *         @Gedmo\SlugHandlerOption(name="relationSlugField", value="slug"),
     *         @Gedmo\SlugHandlerOption(name="separator", value="/")
     *     })
     * }, separator="-", updatable=true, fields={"title"})
     * @ORM\Column(name="slug", type="string", length=64, unique=true)
     */
    #[Gedmo\Slug(separator: '-', updatable: true, fields: ['title'])]
    #[Gedmo\SlugHandler(class: RelativeSlugHandler::class, options: ['relationField' => 'article', 'relationSlugField' => 'slug', 'separator' => '/'])]
    #[ORM\Column(name: 'slug', type: Types::STRING, length: 64, unique: true)]
    private $slug;

    /**
     * @var Article|null
     *
     * @ORM\ManyToOne(targetEntity="Article")
     */
    #[ORM\ManyToOne(targetEntity: Article::class)]
    private $article;

    public function setArticle(Article $article = null): void
    {
        $this->article = $article;
    }

    public function getArticle(): ?Article
    {
        return $this->article;
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }
}
