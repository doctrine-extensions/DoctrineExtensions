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
use Doctrine\ORM\Mapping\Embedded;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sluggable\Sluggable;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class User implements Sluggable
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * @ORM\Column(name="username", type="string", length=64)
     */
    #[ORM\Column(name: 'username', type: Types::STRING, length: 64)]
    private ?string $username = null;

    /**
     * @Gedmo\Slug(separator="-", updatable=true, fields={"username", "address.city", "address.country"})
     *
     * @ORM\Column(name="slug", type="string", length=64, unique=true)
     */
    #[Gedmo\Slug(separator: '-', updatable: true, fields: ['username', 'address.city', 'address.country'])]
    #[ORM\Column(name: 'slug', type: Types::STRING, length: 64, unique: true)]
    private ?string $slug = null;

    /**
     * @ORM\Embedded(class=Address::class)
     */
    #[Embedded(class: Address::class)]
    private Address $address;

    public function __construct()
    {
        $this->address = new Address();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }

    public function setSlug(?string $slug): void
    {
        $this->slug = $slug;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function setAddress(Address $address): void
    {
        $this->address = $address;
    }
}
