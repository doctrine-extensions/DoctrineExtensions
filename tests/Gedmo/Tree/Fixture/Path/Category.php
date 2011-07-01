<?php

namespace Tree\Fixture\Path;

use Gedmo\Tree\Node as NodeInterface;

/**
 * @author Michael Williams <michael.williams@funsational.com>
 * 
 * @Document(repositoryClass="Gedmo\Tree\Document\Repository\PathRepository")
 * @gedmo:Tree(type="path")
 */
class Category implements NodeInterface
{
    /** @Id */
    private $id;

    /**
     * @gedmo:TreePathSource
     * @String
     */
    private $title;

    /**
     * @gedmo:TreePath
     * @String
     */
    private $path = '';

    /**
     * @gedmo:TreeParent
     * @ReferenceOne(targetDocument="Category")
     */
    private $parent = null;
    
    /**
     * @gedmo:TreeSort
     * @Increment
     */
    private $sortOrder = 0;
    
    /**
     * @gedmo:TreeChildCount
     * @Increment
     */
    private $childCount = 0;

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

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }
    
    public function setParent(Category $parent)
    {
        $this->parent = $parent;
    }
    
    public function getParent()
    {
        return $this->parent;
    }

    public function getSortOrder()
    {
        return $this->sortOrder;
    }
    
    public function getChildCount()
    {
        return $this->childCount;
    }
}
