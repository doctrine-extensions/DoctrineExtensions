<?php

namespace Sluggable\Fixture\Issue104;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Car extends Vehicle
{
    /**
     * @ORM\Column(length=128)
     */
    protected $title;

    /**
     * @ORM\Column(length=128)
     */
    private $description;

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getDescription()
    {
        return $this->description;
    }
}
