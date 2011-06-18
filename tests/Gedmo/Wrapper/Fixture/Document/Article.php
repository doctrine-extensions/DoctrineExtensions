<?php

namespace Wrapper\Fixture\Document;

/**
 * @Document(collection="articles")
 */
class Article
{
    /** @Id */
    private $id;

    /**
     * @String
     */
    private $title;

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
