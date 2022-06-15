<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class Position
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(length=16)
     */
    #[ORM\Column(length: 16)]
    private $prop;

    /**
     * @var string|null
     *
     * @ORM\Column(length=64)
     */
    #[ORM\Column(length: 64)]
    private $title;

    /**
     * @var string|null
     *
     * @ORM\Column(length=16)
     */
    #[ORM\Column(length: 16)]
    private $code;

    /**
     * @var string|null
     *
     * @ORM\Column(length=16)
     */
    #[ORM\Column(length: 16)]
    private $other;

    /**
     * @var string|null
     *
     * @Gedmo\Slug(fields={"code", "other", "title", "prop"})
     * @ORM\Column(length=64, unique=true)
     */
    #[Gedmo\Slug(fields: ['code', 'other', 'title', 'prop'])]
    #[ORM\Column(length: 64, unique: true)]
    private $slug;
}
