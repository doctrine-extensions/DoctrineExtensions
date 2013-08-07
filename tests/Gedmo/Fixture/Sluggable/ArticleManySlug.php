<?php

namespace Gedmo\Fixture\Sluggable;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class ArticleManySlug
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $uniqueTitle;

    /**
     * @Gedmo\Slug(fields={"uniqueTitle"})
     * @ORM\Column(type="string", length=128)
     */
    private $uniqueSlug;

    /**
     * @ORM\Column(type="string", length=16)
     */
    private $code;

    /**
     * @Gedmo\Slug(fields={"title", "code"})
     * @ORM\Column(type="string", length=128)
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

    public function setUniqueTitle($uniqueTitle)
    {
        $this->uniqueTitle = $uniqueTitle;
    }

    public function getUniqueTitle()
    {
        return $this->uniqueTitle;
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

    public function getUniqueSlug()
    {
        return $this->uniqueSlug;
    }
}
