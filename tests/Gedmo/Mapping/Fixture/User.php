<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Tests\Mapping\Mock\Extension\Encoder\Mapping as Ext;

/**
 * @ORM\Table(name="test_users")
 * @ORM\Entity
 */
#[ORM\Table(name: 'test_users')]
#[ORM\Entity]
class User
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    /**
     * @var string|null
     *
     * @Ext\Encode(type="sha1", secret="xxx")
     * @ORM\Column(length=64)
     */
    #[ORM\Column(length: 64)]
    private $name;

    /**
     * @var string|null
     *
     * @Ext\Encode(type="md5")
     * @ORM\Column(length=32)
     */
    #[ORM\Column(length: 32)]
    private $password;

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }
}
