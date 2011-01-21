<?php

namespace Sluggable\Fixture\Document;

/** 
 * @Document(collection="users")
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

    /**
     * @gedmo:Sluggable
     * @String
     */
    private $code;
    
    /**
     * @gedmo:Slug
     * @String
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
