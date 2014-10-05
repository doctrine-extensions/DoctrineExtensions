<?php

namespace Sluggable\Fixture\Issue827;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Post
{
    /**
     * @ORM\Id
     * @ORM\Column(name="title", unique=true, length=64)
     */
    private $title;

    /**
     * @ORM\Id
     * @Gedmo\Slug(updatable=true, unique=true, fields={"title"})
     * @ORM\Column(length=64, nullable=true)
     */
    private $slug;

    /**
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="Post")
     */
    private $comments;

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
