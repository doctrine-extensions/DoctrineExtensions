<?php

namespace Tree\Fixture;

use DoctrineExtensions\Tree\Node,
    DoctrineExtensions\Tree\Configuration as TreeConfiguration,
    DoctrineExtensions\Translatable\Translatable;

/**
 * @Entity(repositoryClass="Tree\Fixture\Repository\BehavioralCategoryRepository")
 */
class BehavioralCategory implements Node
{
    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue
     */
    private $id;

    /**
     * @Translatable
     * @Sluggable
     * @Column(name="title", type="string", length=64)
     */
    private $title;

    /**
     * @Column(name="lft", type="integer", nullable=true)
     */
    private $lft;
    
    /**
     * @Column(name="rgt", type="integer", nullable=true)
     */
    private $rgt;
    
    /**
     * @ManyToOne(targetEntity="Tree\Fixture\BehavioralCategory", inversedBy="children")
     */
    private $parent;
    
    /**
     * @OneToMany(targetEntity="Tree\Fixture\BehavioralCategory", mappedBy="parent")
     */
    private $children;
    
    /**
     * @Translatable
     * @Slug
     * @Column(name="slug", type="string", length=128)
     */
    private $slug;

    public function getId()
    {
        return $this->id;
    }
    
    public function getSlug()
    {
        return $this->slug;
    }
    
    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }
    
    public function setParent(BehavioralCategory $parent)
    {
        $this->parent = $parent;    
    }
    
    public function getParent()
    {
        return $this->parent;    
    }
    
    public function getTreeConfiguration()
    {
        return new TreeConfiguration();
    }
}
