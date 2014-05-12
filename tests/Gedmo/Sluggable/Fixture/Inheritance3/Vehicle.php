<?php

namespace Sluggable\Fixture\Inheritance3;

use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator", type="string")
 * @ORM\DiscriminatorMap({
 *      "vehicle" = "Vehicle",
 *      "vehiclewithslug" = "VehicleWithSlug",
 *      "bike" = "Bike",
 *      "car" = "Car"
 * })
 */
class Vehicle
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(length=128)
     */
    private $title;

    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    
}