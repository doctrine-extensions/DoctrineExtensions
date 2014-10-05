<?php

namespace Sortable\Fixture\Transport;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Reservation
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Bus")
     */
    private $bus;

    /**
     * Bus destination
     *
     * @Gedmo\SortableGroup
     * @ORM\Column(length=255)
     */
    private $destination;

    /**
     * @Gedmo\SortableGroup
     * @ORM\Column(type="datetime")
     */
    private $travelDate;

    /**
     * @Gedmo\SortablePosition
     * @ORM\Column(type="integer")
     */
    private $seat;

    /**
     * @ORM\Column(length=255)
     */
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
