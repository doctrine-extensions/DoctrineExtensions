<?php

namespace Fixture\Translatable\Transport;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator", type="string")
 * @ORM\DiscriminatorMap({
 *      "vehicle" = "Vehicle",
 *      "car" = "Car",
 *      "motorcycle" = "Motorcycle"
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
     * @ORM\OneToOne(targetEntity="Engine")
     */
    private $engine;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(length=128)
     */
    private $title;

    /**
     * @ORM\OneToMany(targetEntity="VehicleTranslation", mappedBy="object", cascade={"persist"})
     */
    private $translations;

    /**
     * @ORM\Column(type="integer")
     */
    private $speed;

    public function setSpeed($speed)
    {
        $this->speed = $speed;
        return $this;
    }

    public function getSpeed()
    {
        return $this->speed;
    }

    public function __construct()
    {
        $this->translations = new ArrayCollection;
    }

    public function getTranslations()
    {
        return $this->translations;
    }

    public function addTranslation(VehicleTranslation $translation)
    {
        if (!$this->translations->contains($translation)) {
            $this->translations[] = $translation;
            $translation->setObject($this);
            // if translation is added in the collection, make sure the main fields aren't blank
            $this->title = $translation->getTitle();
        }
    }

    public function getId()
    {
        return $this->id;
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
