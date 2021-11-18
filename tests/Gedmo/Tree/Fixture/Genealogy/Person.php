<?php

namespace Gedmo\Tests\Tree\Fixture\Genealogy;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 * @ORM\Table(name="genealogy")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"man" = "Man", "woman" = "Woman"})
 * @Gedmo\Tree(type="nested")
 */
abstract class Person
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
     * @ORM\ManyToOne(targetEntity="Person", inversedBy="children")
     *
     * @var Person
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="Person", mappedBy="parent")
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
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
     * @ORM\Column(name="name", type="string", length=191, nullable=false)
     *
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->children = new ArrayCollection();
    }

    /**
     * @return Person
     */
    public function setParent(self $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    public function getName()
    {
        return $this->name;
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
