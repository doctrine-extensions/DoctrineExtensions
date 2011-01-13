<?php
namespace Tree\Fixture;

/**
 * Group entity
 * 
 * @Entity(repositoryClass="Gedmo\Tree\Repository\TreeNodeRepository")
 * @Table(name="`user_group`")
 */
class UserGroup extends Role {

  /**
   * @Column(name="name", type="string", length=255)
   * @var string
   */
  private $name;

  public function __construct($name) {
    $this->setName($name);
  }

  /**
   * @return string
   */
  public function getRoleId() {
    return $this->name;
  }

  public function getName() {
    return $this->name;
  }

  public function setName($name) {
    $this->name = $name;
    $this->setRoleId($name);
    return $this;
  }

}
