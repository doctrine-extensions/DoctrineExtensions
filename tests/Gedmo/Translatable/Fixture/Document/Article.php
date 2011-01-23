<?php

namespace Translatable\Fixture\Document;

/** 
 * @Document(collection="articles")
 */
class Article
{
    /** @Id */
    private $id;

    /**
     * @gedmo:Sluggable
     * @gedmo:Translatable
     * @String
     */
    private $title;

    /**
     * @gedmo:Sluggable
     * @gedmo:Translatable
     * @String
     */
    private $code;
    
    /**
     * @gedmo:Slug
     * @gedmo:Translatable
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
