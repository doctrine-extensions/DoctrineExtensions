<?php

namespace Tree\Fixture\Transport;

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discriminator", type="string")
 * @DiscriminatorMap({
 *      "vehicle" = "Vehicle",
 *      "car" = "Car",
 *      "bus" = "Bus"
 * })
 */
class Vehicle
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    private $id;

    /**
     * @OneToOne(targetEntity="Engine")
     */
    private $engine;

    /**
     * @Column(length=128)
     */
    private $title;

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