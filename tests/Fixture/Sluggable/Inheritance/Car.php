<?php

namespace Fixture\Sluggable\Inheritance;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 */
class Car extends Vehicle
{
    /**
     * @ORM\Column(length=128)
     */
    protected $title;

    /**
     * @Gedmo\Slug(fields={"title", "type"})
     * @ORM\Column(length=128, unique=true)
     */
    private $slug;

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
