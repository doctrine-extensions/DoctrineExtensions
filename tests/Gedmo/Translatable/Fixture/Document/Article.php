<?php

namespace Translatable\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoODM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
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
     * @Gedmo\Translatable
     * @MongoODM\Field(type="string")
     */
    private $code;

    /**
     * @Gedmo\Slug(fields={"title", "code"})
     * @Gedmo\Translatable
     * @MongoODM\Field(type="string")
     */
    private $slug;

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
