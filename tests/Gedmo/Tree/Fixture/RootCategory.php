<?php

namespace Tree\Fixture;

/**
 * @Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 * @gedmo:Tree(type="nested")
 */
class RootCategory
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
     * @ManyToOne(targetEntity="RootCategory", inversedBy="children")
     * @JoinColumns({
     *   @JoinColumn(name="parent_id", referencedColumnName="id", onDelete="SET NULL")
     * })
     */
    private $parent;

    /**
     * @gedmo:TreeRoot
     * @Column(type="integer", nullable=true)
     */
    private $root;

    /**
     * @gedmo:TreeLevel
     * @Column(name="lvl", type="integer")
     */
     private $level;

    /**
     * @OneToMany(targetEntity="RootCategory", mappedBy="parent")
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

    public function setParent(RootCategory $parent = null)
    {
        $this->parent = $parent;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getRoot()
    {
        return $this->root;
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
        return $this->level;
    }
}
