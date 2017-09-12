<?php

namespace Loggable\Fixture\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Class Address
 * @package Loggable\Fixture\Entity
 * @author Fabian Sabau <fabian.sabau@socialbit.de>
 *
 * @ORM\Entity()
 * @Gedmo\Loggable()
 */
class Address
{
    /**
     * @var string $id
     * @ORM\Id()
     * @ORM\Column(type="string", length=36)
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $id;

    /**
     * @var string $street
     * @ORM\Column(type="string", length=255)
     * @Gedmo\Versioned()
     */
    protected $street;

    /**
     * @var string $city
     * @ORM\Column(type="string", length=255)
     * @Gedmo\Versioned()
     */
    protected $city;

    /**
     * @var Geo $geo
     * @ORM\Embedded(class="Loggable\Fixture\Entity\Geo")
     * @Gedmo\Versioned()
     */
    protected $geo;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * @param string $street
     * @return $this
     */
    public function setStreet($street)
    {
        $this->street = $street;

        return $this;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param string $city
     * @return $this
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return Geo
     */
    public function getGeo()
    {
        return $this->geo;
    }

    /**
     * @param Geo $geo
     * @return $this
     */
    public function setGeo($geo)
    {
        $this->geo = $geo;

        return $this;
    }

}
