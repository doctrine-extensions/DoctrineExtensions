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
