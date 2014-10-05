<?php
/**
 * Created by Dirk Luijk (dirk@luijkwebcreations.nl)
 * 2013
 */

namespace Sluggable\Fixture;

use Gedmo\Sluggable\Sluggable;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Prefix implements Sluggable
{
    /**
    * @ORM\Id
    * @ORM\GeneratedValue
    * @ORM\Column(type="integer")
    */
    private $id;

    /**
     * @ORM\Column(name="title", type="string", length=64)
     */
    private $title;

    /**
     * @Gedmo\Slug(separator="-", updatable=true, fields={"title"}, prefix="test-")
     * @ORM\Column(name="slug", type="string", length=64, unique=true)
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

    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    public function getSlug()
    {
        return $this->slug;
    }
}
