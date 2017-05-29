<?php

namespace Loggable\Fixture\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Class GeoLocation
 * @package Loggable\Fixture
 * @author Fabian Sabau <fabian.sabau@socialbit.de>
 *
 * @ORM\Embeddable()
 */
class GeoLocation
{
    /**
     * @var string $latitude
     * @ORM\Column(type="string")
     * @Gedmo\Versioned()
     */
    protected $location;

    /**
     * Geo constructor.
     * @param string $location
     */
    public function __construct($location)
    {
        $this->location = $location;
    }

    /**
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param string $location
     */
    public function setLocation($location)
    {
        $this->location = $location;
    }
}
