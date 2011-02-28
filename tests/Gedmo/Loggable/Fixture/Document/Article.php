<?php

namespace Loggable\Fixture\Document;

/**
 * @Document(collection="articles")
 * @gedmo:Loggable
 */
class Article
{
    /** @Id */
    private $id;

    /**
     * @gedmo:Sluggable
     * @String
     */
    private $title;

    public function __toString()
    {
        return $this->title;
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
}
