<?php

namespace Tree\Fixture\Transport;

/**
 * @gedmo:Tree(type="nested")
 * @Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 */
class Car extends Vehicle
{
    /**
     * @gedmo:TreeParent
     * @ManyToOne(targetEntity="Car", inversedBy="children")
     * @JoinColumns({
     *   @JoinColumn(name="parent_id", referencedColumnName="id", onDelete="SET NULL")
     * })
     */
    private $parent;

    /**
     * @OneToMany(targetEntity="Car", mappedBy="parent")
     */
    private $children;

    /**
     * @gedmo:TreeLeft
     * @Column(type="integer", nullable=true)
     */
    private $lft;

    /**
     * @gedmo:TreeRight
     * @Column(type="integer", nullable=true)
     */
    private $rgt;

    /**
     * @gedmo:TreeRoot
     * @Column(type="integer", nullable=true)
     */
    private $root;

    /**
     * @gedmo:TreeLevel
     * @Column(name="lvl", type="integer", nullable=true)
     */
    private $classLevel;

    public function setParent($parent = null)
    {
        $this->parent = $parent;
    }

    public function getChildren()
    {
        return $this->children;
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

    public function getClassLevel()
    {
        return $this->classLevel;
    }
}