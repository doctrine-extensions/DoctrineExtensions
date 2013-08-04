<?php
namespace Fixture\Sluggable\Genealogy;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\Table(name="genealogy")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"man" = "Man", "woman" = "Woman", "employee" = "Employee"})
 */
abstract class Person
{

  /**
   * @ORM\Column(type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue
   */
  private $id;

  /**
   * @ORM\Column(length=64)
   */
  private $name;

  /**
   * @ORM\Column(length=32)
   */
  private $region;


  /**
   * @ORM\Column(length=64)
   * @Gedmo\Slug(fields={"name", "region"})
   */
  private $uri;

  public function setUri($uri)
  {
      $this->uri = $uri;
      return $this;
  }

  public function getUri()
  {
      return $this->uri;
  }

  public function setName($name)
  {
      $this->name = $name;
      return $this;
  }

  public function getName()
  {
      return $this->name;
  }

  public function setRegion($region)
  {
      $this->region = $region;
      return $this;
  }

  public function getRegion()
  {
      return $this->region;
  }
}
