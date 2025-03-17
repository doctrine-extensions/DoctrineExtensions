<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Blameable\Fixture\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Blameable;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV6;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class Company implements Blameable
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
     * @ORM\Column(name="name", type="string", length=128)
     */
    #[ORM\Column(name: 'name', type: Types::STRING, length: 128)]
    private ?string $name = null;

    /**
     * @Gedmo\Blameable(on="create")
     *
     * @ORM\Column(name="created", type="uuid")
     */
    #[ORM\Column(name: 'created', type: 'uuid')]
    #[Gedmo\Blameable(on: 'create')]
    private UuidV6|string|null $created = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getCreated(): ?Uuid
    {
        return $this->created;
    }

    public function setCreated(?UuidV6 $created): void
    {
        $this->created = $created;
    }
}
