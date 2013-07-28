<?php

namespace Fixture\Sortable\Transport;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discriminator", type="string")
 * @ORM\DiscriminatorMap({
 *      "vehicle" = "Vehicle",
 *      "car" = "Car",
 *      "bus" = "Bus"
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
     * @ORM\ManyToOne(targetEntity="Engine")
     */
    private $engine;

    /**
     * @ORM\Column(length=128)
     */
    private $title;

    /**
     * @Gedmo\Sortable(groups={"engine"})
     * @ORM\Column(type="integer")
     */
    private $sortByEngine;

    public function getId()
    {
        return $this->id;
    }

    public function setSortByEngine($sort)
    {
        $this->sortByEngine = $sort;
    }

    public function getSortByEngine()
    {
        return $this->sortByEngine;
    }

    public function setEngine(Engine $engine)
    {
        $this->engine = $engine;
    }

    public function getEngine()
    {
        return $this->engine;
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
