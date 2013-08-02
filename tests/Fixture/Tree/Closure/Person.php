<?php

namespace Fixture\Tree\Closure;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Gedmo\Tree(type="closure")
 * @Gedmo\TreeClosure(class="Fixture\Tree\Closure\PersonClosure")
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\ClosureTreeRepository")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discriminator", type="string")
 * @ORM\DiscriminatorMap({
    "user" = "User"
    })
 */
class Person
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(name="full_name", type="string", length=64)
     */
    private $fullName;

    /**
     * @Gedmo\TreeParent
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\ManyToOne(targetEntity="Person", inversedBy="children", cascade={"persist"})
     */
    private $parent;

    /**
     * @ORM\Column(name="level", type="integer")
     * @Gedmo\TreeLevel
     */
    private $level;

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setParent(Category $parent = null)
    {
        $this->parent = $parent;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function addClosure(CategoryClosure $closure)
    {
        $this->closures[] = $closure;
    }

    public function setLevel($level)
    {
        $this->level = $level;
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function setFullName($fullName)
    {
        $this->fullName = $fullName;
    }

    public function getFullName()
    {
        return $this->fullName;
    }
}
