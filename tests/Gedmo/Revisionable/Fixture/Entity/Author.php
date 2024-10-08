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
 * @ORM\Embeddable
 *
 * @Gedmo\Revisionable
 */
#[ORM\Embeddable]
#[Gedmo\Revisionable]
class Author implements Revisionable
{
    /**
     * @ORM\Column(name="name", type="string")
     *
     * @Gedmo\Versioned
     */
    #[ORM\Column(name: 'name', type: Types::STRING)]
    #[Gedmo\Versioned]
    private ?string $name = null;

    /**
     * @ORM\Column(name="email", type="string")
     *
     * @Gedmo\Versioned
     */
    #[ORM\Column(name: 'email', type: Types::STRING)]
    #[Gedmo\Versioned]
    private ?string $email = null;

    public function __toString()
    {
        return (string) $this->getName();
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }
}
