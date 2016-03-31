<?php

namespace Sluggable\Fixture\Issue1058;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 */
class Page
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(name="title", type="string", length=64)
     */
    protected $title;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Sluggable\Fixture\Issue1058\User")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $user;

    /**
     * @Gedmo\Slug(separator="-", fields={"title"}, unique=true, unique_base="user")
     * @ORM\Column(name="slug", type="string", length=64)
     */
    protected $slug;

    /**
     * Getter of Id
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Setter of Slug
     *
     * @param string $slug
     *
     * @return $this
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Getter of Slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Setter of Title
     *
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Getter of Title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return $this
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }
}
