<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sortable\Fixture\Transport;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

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
     * @Gedmo\SortableGroup
     * @ORM\ManyToOne(targetEntity="Engine")
     */
    private $engine;

    /**
     * @ORM\Column(length=128)
     */
    private $title;

    /**
     * @Gedmo\SortablePosition
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
