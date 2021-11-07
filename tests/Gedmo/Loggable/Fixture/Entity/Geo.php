<?php

namespace Gedmo\Tests\Loggable\Fixture\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Class Geo
 *
 * @author Fabian Sabau <fabian.sabau@socialbit.de>
 *
 * @ORM\Embeddable()
 */
class Geo
{
    /**
     * @var string
     * @ORM\Column(type="decimal", precision=9, scale=6)
     * @Gedmo\Versioned()
     */
    protected $latitude;

    /**
     * @var string
     * @ORM\Column(type="decimal", precision=9, scale=6)
     * @Gedmo\Versioned()
     */
    protected $longitude;

    /**
     * @var GeoLocation
     * @ORM\Embedded(class="Gedmo\Tests\Loggable\Fixture\Entity\GeoLocation")
     * @Gedmo\Versioned()
     */
    protected $geoLocation;

    /**
     * Geo constructor.
     *
     * @param string $latitude
     * @param string $longitude
     */
    public function __construct($latitude, $longitude, GeoLocation $geoLocation)
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->geoLocation = $geoLocation;
    }

    /**
     * @return string
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @param string $latitude
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
    }

    /**
     * @return string
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * @param string $longitude
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
    }

    /**
     * @return GeoLocation
     */
    public function getGeoLocation()
    {
        return $this->geoLocation;
    }

    /**
     * @param GeoLocation $geoLocation
     */
    public function setGeoLocation($geoLocation)
    {
        $this->geoLocation = $geoLocation;
    }
}
