<?php

namespace Translatable\Fixture\Document;

/**
 * @Document(collection="articles")
 */
class SimpleArticle
{
    /** @Id */
    private $id;

    /**
     * @gedmo:Translatable
     * @String
     */
    private $title;

    /**
     * @gedmo:Translatable
     * @String
     */
    private $content;

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
}
