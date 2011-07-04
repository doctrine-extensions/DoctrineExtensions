<?php
namespace Tree\Fixture;

use Doctrine\ORM\Mapping as ORM;

/**
 * Group entity
 *
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 * @ORM\Table(name="`user_group`")
 */
class UserGroup extends Role {

  /**
   * @ORM\Column(name="name", type="string", length=255)
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
