<?php

namespace Tree\Fixture;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\MaterializedPathRepository")
 * @Gedmo\Tree(type="materializedPath")
 */
class MPCategoryWithRootAssociation
{
    /**
     * @Gedmo\TreePathSource
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @Gedmo\TreePath
     * @ORM\Column(name="path", type="string", length=3000, nullable=true)
     */
    private $path;

    /**
     * @ORM\Column(name="title", type="string", length=64)
     */
    private $title;

    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="MPCategoryWithRootAssociation", inversedBy="children")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $parentId;

    /**
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer", nullable=true)
     */
    private $level;

    /**
     * @Gedmo\TreeRoot
     * @ORM\ManyToOne(targetEntity="MPCategoryWithRootAssociation")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tree_root_entity", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $treeRootEntity;

    /**
     * @ORM\OneToMany(targetEntity="MPCategory", mappedBy="parent")
     */
    private $children;

    /**
     * @ORM\OneToMany(targetEntity="Article", mappedBy="category")
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

    public function setParent(MPCategoryWithRootAssociation $parent = null)
    {
        $this->parentId = $parent;
    }

    public function getParent()
    {
        return $this->parentId;
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function getTreeRootEntity()
    {
        return $this->treeRootEntity;
    }
}
