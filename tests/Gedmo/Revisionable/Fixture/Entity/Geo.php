<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Revisionable\Fixture\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @author Fabian Sabau <fabian.sabau@socialbit.de>
 *
 * @ORM\Embeddable
 */
#[ORM\Embeddable]
class Geo
{
    /**
     * @var numeric-string
     *
     * @ORM\Column(type="decimal", precision=9, scale=6)
     *
     * @Gedmo\Versioned
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 9, scale: 6)]
    #[Gedmo\Versioned]
    private string $latitude;

    /**
     * @var numeric-string
     *
     * @ORM\Column(type="decimal", precision=9, scale=6)
     *
     * @Gedmo\Versioned
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 9, scale: 6)]
    #[Gedmo\Versioned]
    private string $longitude;

    /**
     * @ORM\Embedded(class="Gedmo\Tests\Revisionable\Fixture\Entity\GeoLocation")
     *
     * @Gedmo\Versioned
     */
    #[ORM\Embedded(class: GeoLocation::class)]
    #[Gedmo\Versioned]
    private GeoLocation $geoLocation;

    public function __construct(float $latitude, float $longitude, GeoLocation $geoLocation)
    {
        $this->latitude = $this->parseFloatToString($latitude);
        $this->longitude = $this->parseFloatToString($longitude);
        $this->geoLocation = $geoLocation;
    }

    public function getLatitude(): float
    {
        return (float) $this->latitude;
    }

    public function setLatitude(float $latitude): void
    {
        $this->latitude = $this->parseFloatToString($latitude);
    }

    public function getLongitude(): float
    {
        return (float) $this->longitude;
    }

    public function setLongitude(float $longitude): void
    {
        $this->longitude = $this->parseFloatToString($longitude);
    }

    public function getGeoLocation(): GeoLocation
    {
        return $this->geoLocation;
    }

    public function setGeoLocation(GeoLocation $geoLocation): void
    {
        $this->geoLocation = $geoLocation;
    }

    /**
     * @return numeric-string
     */
    private function parseFloatToString(float $number): string
    {
        return sprintf('%.6f', $number);
    }
}
