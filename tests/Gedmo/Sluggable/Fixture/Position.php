<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

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
