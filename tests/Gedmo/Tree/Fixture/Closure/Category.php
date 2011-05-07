<?php

namespace Tree\Fixture\Closure;

/**
 * @gedmo:Tree(type="closure")
 * @gedmo:TreeClosure(class="Tree\Fixture\Closure\CategoryClosure")
 * @Entity(repositoryClass="Gedmo\Tree\Entity\Repository\ClosureTreeRepository")
 */
class Category
{
    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue
     */
    private $id;

    /**
     * @Column(name="title", type="string", length=64)
     */
    private $title;

    /**
     * @gedmo:TreeParent
     * @JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * @ManyToOne(targetEntity="Category", inversedBy="children", cascade={"persist"})
     */
    private $parent;

    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
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
