<?php

namespace Sluggable\Fixture;

use DoctrineExtensions\Sluggable\Sluggable,
    DoctrineExtensions\Sluggable\Configuration;

/**
 * @Entity
 */
class ConfigurationArticle implements Sluggable
{
    /** @Id @GeneratedValue @Column(type="integer") */
    private $id;

    /**
     * @Column(name="title", type="string", length=64)
     */
    private $title;

    /**
     * @Column(name="code", type="string", length=16)
     */
    private $code;
    
    /**
     * @Column(name="slug", type="string", length=128)
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
    
    public function getSluggableConfiguration()
    {
        $config = new Configuration();
        $config->setSluggableFields(array('title', 'code'));
        $config->setSlugField('slug');
        $config->setIsUpdatable(false);
        $config->setIsUnique(false);
        $config->setLength(32);
        return $config;
    }
    
    public function getSlug()
    {
        return $this->slug;
    }
    
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }
}