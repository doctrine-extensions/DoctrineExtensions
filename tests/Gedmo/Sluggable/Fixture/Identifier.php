<?php

namespace Gedmo\Tests\Sluggable\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 */
class Identifier
{
    /**
     * @ORM\Id
     * @Gedmo\Slug(separator="_", updatable=false, fields={"title"})
     * @ORM\Column(length=32, unique=true)
     */
    private $id;

    /**
     * @ORM\Column(length=32)
     */
    private $title;

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
}
