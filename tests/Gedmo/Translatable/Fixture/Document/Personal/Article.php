<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Translatable\Fixture\Document\Personal;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoODM;
use Doctrine\ODM\MongoDB\Types\Type;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tests\Translatable\Fixture\Personal\PersonalArticleTranslation;

/**
 * @Gedmo\TranslationEntity(class="Gedmo\Tests\Translatable\Fixture\Document\Personal\ArticleTranslation")
 * @MongoODM\Document(collection="articles")
 */
#[Gedmo\TranslationEntity(class: ArticleTranslation::class)]
#[MongoODM\Document(collection: 'articles')]
class Article
{
    /** @MongoODM\Id */
    #[MongoODM\Id]
    private $id;

    /**
     * @Gedmo\Translatable
     * @MongoODM\Field(type="string")
     */
    #[Gedmo\Translatable]
    #[MongoODM\Field(type: Type::STRING)]
    private $title;

    /**
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

    public function getTranslations()
    {
        return $this->translations;
    }

    public function addTranslation(PersonalArticleTranslation $t): void
    {
        if (!$this->translations->contains($t)) {
            $this->translations[] = $t;
            $t->setObject($this);
        }
    }

    public function getId()
    {
        return $this->id;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setCode($code): void
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
