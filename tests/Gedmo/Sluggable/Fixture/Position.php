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
     * @ORM\Column(length=16)
     */
    private $prop;

    /**
     * @ORM\Column(length=64)
     */
    private $title;

    /**
     * @ORM\Column(length=16)
     */
    private $code;

    /**
     * @ORM\Column(length=16)
     */
    private $other;

    /**
     * @Gedmo\Slug(fields={"code", "other", "title", "prop"})
     * @ORM\Column(length=64, unique=true)
     */
    private $slug;
}
