<?php

namespace Tree\Fixture\Closure;

/**
 * @Entity
 * @gedmo:Tree(type="closure")
 * @gedmo:TreeClosure(class="Tree\Fixture\Closure\CategoryClosure")
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
     * @ManyToOne(targetEntity="Category", inversedBy="children")
     */
    private $parent;
    
    /**
     * @OneToMany(targetEntity="Category", mappedBy="parent")
     */
    private $children;

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
    
    public function setParent(Category $parent)
    {
        $this->parent = $parent;    
    }
    
    public function getParent()
    {
        return $this->parent;    
    }
}
