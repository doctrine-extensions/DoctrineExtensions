<?php

namespace Sluggable\Fixture\Inheritance2;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discriment", type="string")
 * @ORM\DiscriminatorMap({"vehicle" = "Vehicle", "car" = "Car", "sport" = "SportCar"})
 */
abstract class Vehicle
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    public function getId()
    {
        return $this->id;
    }
}