<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Loggable\Fixture\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Loggable\Loggable;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @author Fabian Sabau <fabian.sabau@socialbit.de>
 */
#[ORM\Entity]
#[Gedmo\Loggable]
class Address implements Loggable
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @var string|null
     */
    #[ORM\Column(type: Types::STRING, length: 191)]
    #[Gedmo\Versioned]
    protected ?string $street = null;

    /**
     * @var string|null
     */
    #[ORM\Column(type: Types::STRING, length: 191)]
    #[Gedmo\Versioned]
    protected ?string $city = null;

    /**
     * @var Geo|null
     */
    #[ORM\Embedded(class: Geo::class)]
    #[Gedmo\Versioned]
    protected $geo;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStreet(): string
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

    public function setCity(string $city): self
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
