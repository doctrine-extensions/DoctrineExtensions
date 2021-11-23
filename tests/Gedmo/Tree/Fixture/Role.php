<?php

namespace Gedmo\Tests\Tree\Fixture;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 * @ORM\Table(name="role")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"user" = "User", "usergroup" = "UserGroup", "userldap" = "UserLDAP"})
 * @Gedmo\Tree(type="nested")
 */
abstract class Role
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     *
     * @var int
     */
    private $id;

    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="UserGroup", inversedBy="children")
     *
     * @var UserGroup
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="Role", mappedBy="parent")
     *
     * @var ArrayCollection
     */
    protected $children;

    /**
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer")
     */
    private $lft;

    /**
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer")
     */
    private $rgt;

    /**
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer")
     */
    private $lvl;

    /**
     * @ORM\Column(name="role", type="string", length=191, nullable=false)
     *
     * @var string
     */
    private $role;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    /**
     * @return UserGroup
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return Role
     */
    public function setParent(UserGroup $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    public function getRoleId()
    {
        return $this->role;
    }

    protected function setRoleId($roleId)
    {
        $this->role = (string) $roleId;

        return $this;
    }

    public function __toString()
    {
        return $this->getRoleId();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getLeft()
    {
        return $this->lft;
    }

    public function getRight()
    {
        return $this->rgt;
    }

    public function getLevel()
    {
        return $this->lvl;
    }
}
