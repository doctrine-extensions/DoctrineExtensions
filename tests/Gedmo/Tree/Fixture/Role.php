<?php
namespace Tree\Fixture;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity(repositoryClass="Gedmo\Tree\Repository\TreeNodeRepository")
 * @Table(name="`role`")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"user" = "User", "usergroup" = "UserGroup"})
 */
abstract class Role {

  /**
   * @Column(name="id", type="integer")
   * @Id
   * @GeneratedValue
   * @var int
   */
  private $id;

  /**
   * @gedmo:TreeParent
   * @ManyToOne(targetEntity="UserGroup", inversedBy="children")
   * @var UserGroup
   */
  private $parent;

  /**
   * @OneToMany(targetEntity="Role", mappedBy="parent")
   * @var Doctrine\Common\Collections\ArrayCollection
   */
  protected $children;

  /**
   * @gedmo:TreeLeft
   * @Column(name="lft", type="integer")
   */
  private $lft;

  /**
   * @gedmo:TreeRight
   * @Column(name="rgt", type="integer")
   */
  private $rgt;

  /**
   * @gedmo:TreeLevel
   * @Column(name="lvl", type="integer")
   */
  private $lvl;

  /**
   * @Column(name="role", type="string", length=255, nullable=false)
   * @var string
   */
  private $role;

  public function __construct() {
    $this->children      = new ArrayCollection();
  }

  /**
   * @return UserGroup
   */
  public function getParent() {
    return $this->parent;
  }

  /**
   * @param UserGroup $parent
   * @return Role
   */
  public function setParent(UserGroup $parent) {
    $this->parent = $parent;
    return $this;
  }

  public function getRoleId() {
    return $this->role;
  }

  protected function setRoleId($roleId) {
    $this->role = (string)$roleId;
    return $this;
  }

  public function __toString() {
    return $this->getRoleId();
  }

  public function getId() {
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
