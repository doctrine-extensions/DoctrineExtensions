<?php

namespace Gedmo\Tests\Translatable\Fixture\Document\Personal;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoODM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tests\Translatable\Fixture\Personal\PersonalArticleTranslation;

/**
 * @Gedmo\TranslationEntity(class="Gedmo\Tests\Translatable\Fixture\Document\Personal\ArticleTranslation")
 * @MongoODM\Document(collection="articles")
 */
class Article
{
    /** @MongoODM\Id */
    private $id;

    /**
     * @Gedmo\Translatable
     * @MongoODM\Field(type="string")
     */
    private $title;

    /**
     * @MongoODM\ReferenceMany(targetDocument="Gedmo\Tests\Translatable\Fixture\Document\Personal\ArticleTranslation", mappedBy="object")
     */
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

    public function addTranslation(PersonalArticleTranslation $t)
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

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setCode($code)
    {
        $this->code = $code;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function getSlug()
    {
        return $this->slug;
    }
}
