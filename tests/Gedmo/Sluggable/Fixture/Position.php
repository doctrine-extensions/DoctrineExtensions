<?php

namespace Sluggable\Fixture;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Position
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Gedmo\Sluggable(position=2)
     * @ORM\Column(length=16)
     */
    private $prop;

    /**
     * @Gedmo\Sluggable(position=1)
     * @ORM\Column(length=64)
     */
    private $title;

    /**
     * @Gedmo\Sluggable
     * @ORM\Column(length=16)
     */
    private $code;

    /**
     * @Gedmo\Sluggable(position=0)
     * @ORM\Column(length=16)
     */
    private $other;

    /**
     * @Gedmo\Slug
     * @ORM\Column(length=64, unique=true)
     */
    private $slug;
}