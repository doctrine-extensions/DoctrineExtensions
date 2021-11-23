<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Fixture\Issue827;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 */
class Comment
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(name="title", length=64)
     */
    private $title;

    /**
     * @ORM\ManyToOne(targetEntity="Post", inversedBy="Comments")
     * @ORM\JoinColumns({
     *    @ORM\JoinColumn(name="post_title", referencedColumnName="title", nullable=false),
     *    @ORM\JoinColumn(name="post_slug", referencedColumnName="slug", nullable=false)
     * })
     */
    private $post;

    /**
     * @Gedmo\Slug(updatable=true, unique=true, unique_base="post", fields={"title"})
     * @ORM\Column(length=64, nullable=true)
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

    public function getSlug()
    {
        return $this->slug;
    }

    public function setPost(Post $post)
    {
        $this->post = $post;
    }

    public function getPost()
    {
        return $this->post;
    }
}
