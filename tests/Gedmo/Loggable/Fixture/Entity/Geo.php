<?php

namespace Loggable\Fixture\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Class Geo
 * @package Loggable\Fixture
 * @author Fabian Sabau <fabian.sabau@socialbit.de>
 *
 * @ORM\Embeddable()
 */
class Geo
{
    /**
     * @var string $latitude
     * @ORM\Column(type="decimal", precision=9, scale=6)
     * @Gedmo\Versioned()
     */
    protected $latitude;

    /**
     * @var string $longitude
     * @ORM\Column(type="decimal", precision=9, scale=6)
     * @Gedmo\Versioned()
     */
    protected $longitude;

    /**
     * Geo constructor.
     * @param string $latitude
     * @param string $longitude
     */
    public function __construct($latitude, $longitude)
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
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
}
