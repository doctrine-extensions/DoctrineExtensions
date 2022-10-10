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
 */
#[ORM\Entity]
class Reservation
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
     * @var Bus|null
     *
     * @ORM\ManyToOne(targetEntity="Bus")
     */
    #[ORM\ManyToOne(targetEntity: Bus::class)]
    private $bus;

    /**
     * Bus destination
     *
     * @var string|null
     *
     * @Gedmo\SortableGroup
     * @ORM\Column(length=191)
     */
    #[Gedmo\SortableGroup]
    #[ORM\Column(length: 191)]
    private $destination;

    /**
     * @var \DateTime|null
     *
     * @Gedmo\SortableGroup
     * @ORM\Column(type="datetime")
     */
    #[Gedmo\SortableGroup]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private $travelDate;

    /**
     * @var int|null
     *
     * @Gedmo\SortablePosition
     * @ORM\Column(type="integer")
     */
    #[Gedmo\SortablePosition]
    #[ORM\Column(type: Types::INTEGER)]
    private $seat;

    /**
     * @var string|null
     *
     * @ORM\Column(length=191)
     */
    #[ORM\Column(length: 191)]
    private $name;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setBus(Bus $bus): void
    {
        $this->bus = $bus;
    }

    public function getBus(): ?Bus
    {
        return $this->bus;
    }

    public function setDestination(?string $destination): void
    {
        $this->destination = $destination;
    }

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setTravelDate(\DateTime $date): void
    {
        $this->travelDate = $date;
    }

    public function getTravelDate(): ?\DateTime
    {
        return $this->travelDate;
    }

    public function setSeat(?int $seat): void
    {
        $this->seat = $seat;
    }

    public function getSeat(): ?int
    {
        return $this->seat;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}
