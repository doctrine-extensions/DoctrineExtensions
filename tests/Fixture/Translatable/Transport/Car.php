<?php

namespace Fixture\Translatable\Transport;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Car extends Vehicle
{
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $doors;

    public function setDoors($doors)
    {
        $this->doors = $doors;
        return $this;
    }

    public function getDoors()
    {
        return $this->doors;
    }
}
