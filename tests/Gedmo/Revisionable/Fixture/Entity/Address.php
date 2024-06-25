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
use Gedmo\Revisionable\Revisionable;

/**
 * @author Fabian Sabau <fabian.sabau@socialbit.de>
 *
 * @ORM\Entity
 *
 * @Gedmo\Revisionable
 */
#[ORM\Entity]
#[Gedmo\Revisionable]
class Address implements Revisionable
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    /**
     * @ORM\Column(type="string", length=191)
     *
     * @Gedmo\Versioned
     */
    #[ORM\Column(type: Types::STRING, length: 191)]
    #[Gedmo\Versioned]
    private ?string $street = null;

    /**
     * @ORM\Column(type="string", length=191)
     *
     * @Gedmo\Versioned
     */
    #[ORM\Column(type: Types::STRING, length: 191)]
    #[Gedmo\Versioned]
    private ?string $city = null;

    /**
     * @ORM\Embedded(class="Gedmo\Tests\Revisionable\Fixture\Entity\Geo")
     *
     * @Gedmo\Versioned
     */
    #[ORM\Embedded(class: Geo::class)]
    #[Gedmo\Versioned]
    private ?Geo $geo = null;

    public function getId(): ?int
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
