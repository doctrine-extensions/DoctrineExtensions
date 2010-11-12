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
     * @Translatable:Field
     * @Sluggable:Field
     * @Column(name="title", type="string", length=64)
     */
    private $title;

    /**
     * @Tree:Left
     * @Column(name="lft", type="integer", nullable=true)
     */
    private $lft;
    
    /**
     * @Tree:Right
     * @Column(name="rgt", type="integer", nullable=true)
     */
    private $rgt;
    
    /**
     * @Tree:Ancestor
     * @ManyToOne(targetEntity="Tree\Fixture\BehavioralCategory", inversedBy="children")
     */
    private $parent;
    
    /**
     * @OneToMany(targetEntity="Tree\Fixture\BehavioralCategory", mappedBy="parent")
     */
    private $children;
    
    /**
     * @Translatable:Field
     * @Sluggable:Slug
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
}
