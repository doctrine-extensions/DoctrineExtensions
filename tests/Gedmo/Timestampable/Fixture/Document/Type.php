<?php

namespace Timestampable\Fixture\Document;

/** 
 * @Document(collection="types")
 */
class Type
{
    /** @Id */
    private $id;

    /**
     * @String
     */
    private $title;
    
    /**
     * @String
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