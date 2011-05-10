<?php

namespace Tree\Fixture\Closure;

/**
 * @gedmo:Tree(type="closure")
 * @gedmo:TreeClosure(class="Tree\Fixture\Closure\PersonClosure")
 * @Entity(repositoryClass="Gedmo\Tree\Entity\Repository\ClosureTreeRepository")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discriminator", type="string")
 * @DiscriminatorMap({
    "user" = "User"
    })
 */
class Person
{
    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue
     */
    private $id;

    /**
     * @Column(name="full_name", type="string", length=64)
     */
    private $fullName;

    /**
     * @gedmo:TreeParent
     * @JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * @ManyToOne(targetEntity="Person", inversedBy="children", cascade={"persist"})
     */
    private $parent;

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
}
