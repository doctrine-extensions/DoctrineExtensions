<?php

namespace Sluggable\Fixture\Issue1240;

use Gedmo\Sluggable\Sluggable;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Article implements Sluggable
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    private $id;

    /**
     * @ORM\Column(name="title", type="string", length=64)
     */
    private $title;

    /**
     * @Gedmo\Slug(separator="+", updatable=true, fields={"title"})
     * @ORM\Column(name="slug", type="string", length=64, unique=true)
     */
    private $slug;

    /**
     * @Gedmo\Slug(separator="+", updatable=true, fields={"title"}, style="camel")
     * @ORM\Column(name="camel_slug", type="string", length=64, unique=true)
     */
    private $camelSlug;

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

    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function getCamelSlug()
    {
        return $this->camelSlug;
    }

    public function setCamelSlug($camelSlug)
    {
        $this->camelSlug = $camelSlug;
    }
}
