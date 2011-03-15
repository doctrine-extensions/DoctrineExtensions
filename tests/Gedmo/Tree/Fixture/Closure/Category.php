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
	 * @JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * @ManyToOne(targetEntity="Category", inversedBy="children", cascade={"persist"})
     */
    private $parent;
    
    /**
     * @OneToMany(targetEntity="Category", mappedBy="parent", cascade={"persist"})
     */
    private $children;
    
    /**
     * @gedmo:TreeChildCount
     * @Column(type="integer", nullable="true")
     */
    private $childCount;

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
    
    public function getChildCount()
    {
        return $this->childCount;
    }
}
