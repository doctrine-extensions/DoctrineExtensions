<?php

namespace Gedmo\Fixture\Translatable\Sluggable;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 */
class Post
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(length=64)
     */
    private $title;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(type="text", nullable=true)
     */
    private $content;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(length=64, nullable=true)
     */
    private $slug;

    /**
     * @ORM\OneToMany(targetEntity="PostTranslation", mappedBy="object", cascade={"ALL"})
     */
    private $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection;
    }

    public function getTranslations()
    {
        return $this->translations;
    }

    public function addTranslation(PostTranslation $translation)
    {
        if (!$this->translations->contains($translation)) {
            $this->translations[] = $translation;
            $translation->setObject($this);
        }
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setSlug($slug)
    {
        $this->slug = $slug;
        return $this;
    }

    public function getSlug()
    {
        return $this->slug;
    }
}
