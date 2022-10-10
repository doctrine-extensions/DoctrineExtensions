<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Translatable\Fixture\Document\Personal;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoODM;
use Doctrine\ODM\MongoDB\Types\Type;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @Gedmo\TranslationEntity(class="Gedmo\Tests\Translatable\Fixture\Document\Personal\ArticleTranslation")
 * @MongoODM\Document(collection="articles")
 */
#[Gedmo\TranslationEntity(class: ArticleTranslation::class)]
#[MongoODM\Document(collection: 'articles')]
class Article
{
    /**
     * @var string|null
     *
     * @MongoODM\Id
     */
    #[MongoODM\Id]
    private $id;

    /**
     * @var string|null
     *
     * @Gedmo\Translatable
     * @MongoODM\Field(type="string")
     */
    #[Gedmo\Translatable]
    #[MongoODM\Field(type: Type::STRING)]
    private $title;

    /**
     * @var Collection<int, ArticleTranslation>
     *
     * @MongoODM\ReferenceMany(targetDocument="Gedmo\Tests\Translatable\Fixture\Document\Personal\ArticleTranslation", mappedBy="object")
     */
    #[MongoODM\ReferenceMany(targetDocument: ArticleTranslation::class, mappedBy: 'object')]
    private $translations;

    /**
     * @var string|null
     */
    private $code;

    /**
     * @var string
     */
    private $slug;

    /**
     * @return Collection<int, ArticleTranslation>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(ArticleTranslation $t): void
    {
        if (!$this->translations->contains($t)) {
            $this->translations[] = $t;
            $t->setObject($this);
        }
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

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }
}
