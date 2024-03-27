<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Tree\Fixture;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

#[ORM\Entity(repositoryClass: NestedTreeRepository::class)]
#[ORM\Table(name: 'role')]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'discr', type: Types::STRING)]
#[ORM\DiscriminatorMap(['user' => User::class, 'usergroup' => UserGroup::class, 'userldap' => UserLDAP::class])]
#[Gedmo\Tree(type: 'nested')]
abstract class Role implements \Stringable
{
    /**
     * @var Collection<int, Role>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    protected Collection $children;

    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: UserGroup::class, inversedBy: 'children')]
    #[Gedmo\TreeParent]
    private ?UserGroup $parent = null;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'lft', type: Types::INTEGER)]
    #[Gedmo\TreeLeft]
    private ?int $lft = null;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'rgt', type: Types::INTEGER)]
    #[Gedmo\TreeRight]
    private ?int $rgt = null;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'lvl', type: Types::INTEGER)]
    #[Gedmo\TreeLevel]
    private ?int $lvl = null;

    #[ORM\Column(name: 'role', type: Types::STRING, length: 191, nullable: false)]
    private ?string $role = null;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->getRoleId();
    }

    public function getParent(): UserGroup
    {
        return $this->parent;
    }

    public function setParent(UserGroup $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getRoleId(): ?string
    {
        return $this->role;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLeft(): ?int
    {
        return $this->lft;
    }

    public function getRight(): ?int
    {
        return $this->rgt;
    }

    public function getLevel(): ?int
    {
        return $this->lvl;
    }

    protected function setRoleId(?string $roleId): self
    {
        $this->role = (string) $roleId;

        return $this;
    }
}
