<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Tree\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

/**
 * Group entity
 *
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 * @ORM\Table(name="user_group")
 */
#[ORM\Entity(repositoryClass: NestedTreeRepository::class)]
#[ORM\Table(name: 'user_group')]
class UserGroup extends Role
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="name", type="string", length=191)
     */
    #[ORM\Column(name: 'name', type: Types::STRING, length: 191)]
    private $name;

    public function __construct(string $name)
    {
        $this->setName($name);
    }

    public function getRoleId(): ?string
    {
        return $this->name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        $this->setRoleId($name);

        return $this;
    }
}
