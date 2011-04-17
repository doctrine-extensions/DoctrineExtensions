<?php

namespace Tree\Fixture;

use Gedmo\Tree\Node as NodeInterface;

/**
 * @Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 * @gedmo:Tree(type="nested")
 */
class Category implements NodeInterface
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
     * @JoinColumns({
     *   @JoinColumn(name="parent_id", referencedColumnName="id", onDelete="SET NULL")
     * })
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
