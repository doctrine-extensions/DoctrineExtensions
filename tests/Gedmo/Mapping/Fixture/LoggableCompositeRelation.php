<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 *
 * @Gedmo\Loggable
 */
class LoggableCompositeRelation
{
    /**
     * @var Loggable
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Loggable")
     */
    private $one;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    private $two;

    /**
     * @ORM\Column(name="title", type="string", length=64)
     *
     * @Gedmo\Versioned
     */
    private ?string $title = null;

    public function getOne(): Loggable
    {
        return $this->one;
    }

    public function getTwo(): int
    {
        return $this->two;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
