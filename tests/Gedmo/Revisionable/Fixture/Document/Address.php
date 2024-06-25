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
use Gedmo\Revisionable\Revisionable;

/**
 * @author Fabian Sabau <fabian.sabau@socialbit.de>
 *
 * @ODM\Document(collection="addresses")
 *
 * @Gedmo\Revisionable
 */
#[ODM\Document(collection: 'addresses')]
#[Gedmo\Revisionable]
class Address implements Revisionable
{
    /**
     * @ODM\Id
     */
    #[ODM\Id]
    private ?string $id = null;

    /**
     * @ODM\Field(type="string")
     *
     * @Gedmo\Versioned
     */
    #[ODM\Field(type: Type::STRING)]
    #[Gedmo\Versioned]
    private ?string $street = null;

    /**
     * @ODM\Field(type="string")
     *
     * @Gedmo\Versioned
     */
    #[ODM\Field(type: Type::STRING)]
    #[Gedmo\Versioned]
    private ?string $city = null;

    /**
     * @ODM\EmbedOne(targetDocument="Gedmo\Tests\Revisionable\Fixture\Document\Geo")
     *
     * @Gedmo\Versioned
     */
    #[ODM\EmbedOne(targetDocument: Geo::class)]
    #[Gedmo\Versioned]
    private ?Geo $geo = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): self
    {
        $this->street = $street;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getGeo(): ?Geo
    {
        return $this->geo;
    }

    public function setGeo(?Geo $geo): self
    {
        $this->geo = $geo;

        return $this;
    }
}
