<?php

namespace Wrapper\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoODM;

/**
 * @MongoODM\Document(collection="articles")
 */
class Article
{
    /** @MongoODM\Id */
    private $id;

    /**
     * @MongoODM\Field(type="string")
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
