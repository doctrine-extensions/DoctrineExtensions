<?php

namespace Fixture\Sluggable\Issue633;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Article
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;
    
    /**
     * @ORM\Column(name="code", type="string", length=16)
     */
    private $code;

    /**
     * @ORM\Column(name="title", length=64)
     */
    private $title;

    /**
     * @Gedmo\Slug(updatable=true, unique=true, unique_base="code", fields={"title"})
     * @ORM\Column(length=64, nullable=true)
     */
    private $slug;

    public function getId()
    {
        return $this->id;
    }
    
    public function setCode($code)
    {
        $this->code = $code;
    }
    
    public function getCode()
    {
        return $this->code;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getSlug()
    {
        return $this->slug;
    }
}
