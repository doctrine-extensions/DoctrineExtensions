<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sortable\Fixture\Transport;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discriminator", type="string")
 * @ORM\DiscriminatorMap({
 *     "vehicle": "Vehicle",
 *     "car": "Car",
 *     "bus": "Bus"
 * })
 */
#[ORM\Entity]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'discriminator', type: Types::STRING)]
#[ORM\DiscriminatorMap(['vehicle' => Vehicle::class, 'car' => Car::class, 'bus' => Bus::class])]
class Vehicle
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
     * @var Engine|null
     *
     * @Gedmo\SortableGroup
     * @ORM\ManyToOne(targetEntity="Engine")
     */
    #[Gedmo\SortableGroup]
    #[ORM\ManyToOne(targetEntity: Engine::class)]
    private $engine;

    /**
     * @var string|null
     *
     * @ORM\Column(length=128)
     */
    #[ORM\Column(length: 128)]
    private $title;

    /**
     * @var int|null
     *
     * @Gedmo\SortablePosition
     * @ORM\Column(type="integer")
     */
    #[Gedmo\SortablePosition]
    #[ORM\Column(type: Types::INTEGER)]
    private $sortByEngine;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setSortByEngine(?int $sort): void
    {
        $this->sortByEngine = $sort;
    }

    public function getSortByEngine(): ?int
    {
        return $this->sortByEngine;
    }

    public function setEngine(Engine $engine): void
    {
        $this->engine = $engine;
    }

    public function getEngine(): ?Engine
    {
        return $this->engine;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }
}
