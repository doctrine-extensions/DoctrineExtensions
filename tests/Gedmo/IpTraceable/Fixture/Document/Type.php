<?php

namespace IpTraceable\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="types")
 */
class Type
{
    /** @ODM\Id */
    private $id;

    /**
     * @ODM\String
     */
    private $title;

    /**
     * @ODM\String
     */
    private $identifier;

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

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }
}