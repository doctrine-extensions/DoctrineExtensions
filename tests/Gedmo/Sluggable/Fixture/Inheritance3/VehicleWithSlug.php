<?php

namespace Sluggable\Fixture\Inheritance3;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
/**
 * @ORM\Entity
 */
class VehicleWithSlug extends Vehicle
{
    /**
     * @Gedmo\Slug(fields={"title"})
     * @ORM\Column(length=128, unique=true)
     */
    private $slug;
    
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
    
    public function getSlug()
    {
        return $this->slug;
    }
}