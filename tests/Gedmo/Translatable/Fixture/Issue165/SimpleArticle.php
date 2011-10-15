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
     * @MongoODM\String
     */
    private $title;

    /**
     * @Gedmo\Translatable
     * @MongoODM\String
     */
    private $content;

    /**
     * @MongoODM\String
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
