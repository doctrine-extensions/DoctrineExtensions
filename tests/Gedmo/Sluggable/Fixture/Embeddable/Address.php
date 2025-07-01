<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Fixture\Embeddable;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable
 */
#[ORM\Embeddable]
class Address
{
    /**
     * @ORM\Column(name="street", type="string", length=64)
     */
    #[ORM\Column(name: 'street', type: Types::STRING, length: 64)]
    private ?string $street = null;

    /**
     * @ORM\Column(name="postalCode", type="string", length=64)
     */
    #[ORM\Column(name: 'postalCode', type: Types::STRING, length: 64)]
    private ?string $postalCode = null;

    /**
     * @ORM\Column(name="city", type="string", length=64)
     */
    #[ORM\Column(name: 'city', type: Types::STRING, length: 64)]
    private ?string $city = null;

    /**
     * @ORM\Column(name="country", type="string", length=64)
     */
    #[ORM\Column(name: 'country', type: Types::STRING, length: 64)]
    private ?string $country = null;

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): void
    {
        $this->street = $street;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): void
    {
        $this->postalCode = $postalCode;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): void
    {
        $this->city = $city;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): void
    {
        $this->country = $country;
    }
}
