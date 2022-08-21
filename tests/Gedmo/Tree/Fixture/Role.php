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

/**
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 * @ORM\Table(name="role")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"user": "User", "usergroup": "UserGroup", "userldap": "UserLDAP"})
 * @Gedmo\Tree(type="nested")
 */
#[ORM\Entity(repositoryClass: NestedTreeRepository::class)]
#[ORM\Table(name: 'role')]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'discr', type: Types::STRING)]
#[ORM\DiscriminatorMap(['user' => User::class, 'usergroup' => UserGroup::class, 'userldap' => UserLDAP::class])]
#[Gedmo\Tree(type: 'nested')]
abstract class Role
{
    /**
     * @var Collection<int, Role>
     *
     * @ORM\OneToMany(targetEntity="Role", mappedBy="parent")
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    protected $children;

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
     * @var UserGroup
     *
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="UserGroup", inversedBy="children")
     */
    #[ORM\ManyToOne(targetEntity: UserGroup::class, inversedBy: 'children')]
    #[Gedmo\TreeParent]
    private $parent;

    /**
     * @var int|null
     *
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer")
     */
    #[ORM\Column(name: 'lft', type: Types::INTEGER)]
    #[Gedmo\TreeLeft]
    private $lft;

    /**
     * @var int|null
     *
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer")
     */
    #[ORM\Column(name: 'rgt', type: Types::INTEGER)]
    #[Gedmo\TreeRight]
    private $rgt;

    /**
     * @var int|null
     *
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer")
     */
    #[ORM\Column(name: 'lvl', type: Types::INTEGER)]
    #[Gedmo\TreeLevel]
    private $lvl;

    /**
     * @var string|null
     *
     * @ORM\Column(name="role", type="string", length=191, nullable=false)
     */
    #[ORM\Column(name: 'role', type: Types::STRING, length: 191, nullable: false)]
    private $role;

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
