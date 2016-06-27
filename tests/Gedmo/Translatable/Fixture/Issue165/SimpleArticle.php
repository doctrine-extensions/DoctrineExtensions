<?php

namespace Translatable\Fixture\Issue165;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoODM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @MongoODM\Document(collection="articles")
 */
class SimpleArticle
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
    private $content;

    /**
     * @MongoODM\Field(type="string")
     */
    private $untranslated;

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

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setUntranslated($untranslated)
    {
        $this->untranslated = $untranslated;
    }

    public function getUntranslated()
    {
        return $this->untranslated;
    }
}
