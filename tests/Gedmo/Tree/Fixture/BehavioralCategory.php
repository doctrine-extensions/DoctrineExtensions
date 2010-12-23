<?php

namespace Tree\Fixture;

/**
 * @Entity(repositoryClass="Tree\Fixture\Repository\BehavioralCategoryRepository")
 */
class BehavioralCategory
{
    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue
     */
    private $id;

    /**
     * @gedmo:Translatable
     * @gedmo:Sluggable
     * @Column(name="title", type="string", length=64)
     */
    private $title;

    /**
     * @gedmo:TreeLeft
     * @Column(name="lft", type="integer", nullable=true)
     */
    private $lft;
    
    /**
     * @gedmo:TreeRight
     * @Column(name="rgt", type="integer", nullable=true)
     */
    private $rgt;
    
    /**
     * @gedmo:TreeParent
     * @ManyToOne(targetEntity="BehavioralCategory", inversedBy="children")
     */
    private $parent;
    
    /**
     * @OneToMany(targetEntity="BehavioralCategory", mappedBy="parent")
     */
    private $children;
    
    /**
     * @gedmo:Translatable
     * @gedmo:Slug
     * @Column(name="slug", type="string", length=128, unique=true)
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
}
