<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Fixture\Document\Handler;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sluggable\Handler\RelativeSlugHandler;

/**
 * @ODM\Document
 */
#[ODM\Document]
class RelativeSlug
{
    /**
     * @var string|null
     *
     * @ODM\Id
     */
    #[ODM\Id]
    private $id;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     */
    #[ODM\Field(type: Type::STRING)]
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
     * @ODM\Field(type="string")
     */
    #[Gedmo\Slug(separator: '-', updatable: true, fields: ['title'])]
    #[Gedmo\SlugHandler(class: RelativeSlugHandler::class, options: ['relationField' => 'article', 'relationSlugField' => 'slug', 'separator' => '/'])]
    #[ODM\Field(type: Type::STRING)]
    private $alias;

    /**
     * @var Article|null
     *
     * @ODM\ReferenceOne(targetDocument="Gedmo\Tests\Sluggable\Fixture\Document\Handler\Article")
     */
    #[ODM\ReferenceOne(targetDocument: Article::class)]
    private $article;

    public function setArticle(Article $article = null): void
    {
        $this->article = $article;
    }

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function getId(): ?string
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
        return $this->alias;
    }
}
