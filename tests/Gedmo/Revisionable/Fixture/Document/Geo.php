<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Revisionable\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @author Fabian Sabau <fabian.sabau@socialbit.de>
 *
 * @ODM\EmbeddedDocument
 */
#[ODM\EmbeddedDocument]
class Geo
{
    /**
     * @ODM\Field(type="float")
     *
     * @Gedmo\KeepRevisions
     */
    #[ODM\Field(type: Type::FLOAT)]
    #[Gedmo\KeepRevisions]
    private float $latitude;

    /**
     * @ODM\Field(type="float")
     *
     * @Gedmo\KeepRevisions
     */
    #[ODM\Field(type: Type::FLOAT)]
    #[Gedmo\KeepRevisions]
    private float $longitude;

    /**
     * @ODM\EmbedOne(targetDocument="Gedmo\Tests\Revisionable\Fixture\Document\GeoLocation")
     *
     * @Gedmo\KeepRevisions
     */
    #[ODM\EmbedOne(targetDocument: GeoLocation::class)]
    #[Gedmo\KeepRevisions]
    private GeoLocation $geoLocation;

    public function __construct(float $latitude, float $longitude, GeoLocation $geoLocation)
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->geoLocation = $geoLocation;
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): void
    {
        $this->latitude = $latitude;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): void
    {
        $this->longitude = $longitude;
    }

    public function getGeoLocation(): GeoLocation
    {
        return $this->geoLocation;
    }

    public function setGeoLocation(GeoLocation $geoLocation): void
    {
        $this->geoLocation = $geoLocation;
    }
}
