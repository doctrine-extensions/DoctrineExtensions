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

#[ORM\Entity]
class Reservation
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Bus::class)]
    private ?Bus $bus = null;

    /**
     * Bus destination
     */
    #[Gedmo\SortableGroup]
    #[ORM\Column(length: 191)]
    private ?string $destination = null;

    #[Gedmo\SortableGroup]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $travelDate = null;

    #[Gedmo\SortablePosition]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $seat = null;

    #[ORM\Column(length: 191)]
    private ?string $name = null;

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
