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
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Bus")
     */
    #[ORM\ManyToOne(targetEntity: Bus::class)]
    private $bus;

    /**
     * Bus destination
     *
     * @Gedmo\SortableGroup
     * @ORM\Column(length=191)
     */
    #[Gedmo\SortableGroup]
    #[ORM\Column(length: 191)]
    private $destination;

    /**
     * @Gedmo\SortableGroup
     * @ORM\Column(type="datetime")
     */
    #[Gedmo\SortableGroup]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private $travelDate;

    /**
     * @Gedmo\SortablePosition
     * @ORM\Column(type="integer")
     */
    #[Gedmo\SortablePosition]
    #[ORM\Column(type: Types::INTEGER)]
    private $seat;

    /**
     * @ORM\Column(length=191)
     */
    #[ORM\Column(length: 191)]
    private $name;

    public function getId()
    {
        return $this->id;
    }

    public function setBus(Bus $bus)
    {
        $this->bus = $bus;
    }

    public function getBus()
    {
        return $this->bus;
    }

    public function setDestination($destination)
    {
        $this->destination = $destination;
    }

    public function getDestination()
    {
        return $this->destination;
    }

    public function setTravelDate(\DateTime $date)
    {
        $this->travelDate = $date;
    }

    public function getTravelDate()
    {
        return $this->travelDate;
    }

    public function setSeat($seat)
    {
        $this->seat = $seat;
    }

    public function getSeat()
    {
        return $this->seat;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}
