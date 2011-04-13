<?php

namespace Sluggable\Fixture;

use Gedmo\Sluggable\Sluggable;

/**
 * @Entity
 */
class Post implements Sluggable
{
    /** @Id @GeneratedValue @Column(type="integer") */
    private $id;

    /**
     * @gedmo:Sluggable(position=1)
     * @Column(name="title", type="string", length=64)
     */
    private $title;

    /**
     * @gedmo:Sluggable(position=0)
     * @Column(name="code", type="string", length=16)
     */
    private $code;
    
    /**
     * @gedmo:Slug
     * @Column(name="slug", type="string", length=64, unique=true)
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
