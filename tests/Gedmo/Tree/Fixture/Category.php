<?php

namespace Tree\Fixture;

use Gedmo\Tree\Node;

/**
 * @Entity(repositoryClass="Gedmo\Tree\Repository\TreeNodeRepository")
 */
class Category implements Node
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
     * @gedmo:TreeParent
     * @ManyToOne(targetEntity="Category", inversedBy="children")
     */
    private $parentId;
    
    /**
     * @gedmo:TreeLevel
     * @Column(name="lvl", type="integer")
     */
     private $level;
    
    /**
     * @OneToMany(targetEntity="Category", mappedBy="parent")
     */
    private $children;
    
    /**
     * @OneToMany(targetEntity="Article", mappedBy="category")
     */
    private $comments;

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
        $this->parentId = $parent;    
    }
    
    public function getParent()
    {
        return $this->parentId;    
    }
}
