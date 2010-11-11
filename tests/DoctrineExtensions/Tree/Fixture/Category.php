<?php

namespace Tree\Fixture;

use DoctrineExtensions\Tree\Node;

/**
 * @Entity(repositoryClass="DoctrineExtensions\Tree\Repository\TreeNodeRepository")
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
     * @Tree:Left
     * @Column(name="lft", type="integer")
     */
    private $lft;
    
    /**
     * @Tree:Right
     * @Column(name="rgt", type="integer")
     */
    private $rgt;
    
    /**
     * @Tree:Ancestor
     * @ManyToOne(targetEntity="Tree\Fixture\Category", inversedBy="children")
     */
    private $parent;
    
    /**
     * @OneToMany(targetEntity="Tree\Fixture\Category", mappedBy="parent")
     */
    private $children;
    
    /**
     * @OneToMany(targetEntity="Tree\Fixture\Article", mappedBy="category")
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
        $this->parent = $parent;    
    }
    
    public function getParent()
    {
        return $this->parent;    
    }
}
