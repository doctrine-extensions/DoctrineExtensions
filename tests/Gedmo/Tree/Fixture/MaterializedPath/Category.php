<?php

namespace Tree\Fixture\MaterializedPath;

use Gedmo\Tree\Node as NodeInterface;

/**
 * @author Michael Williams <michael.williams@funsational.com>
 * 
 * @Entity(repositoryClass="Gedmo\Tree\Document\Repository\MaterializedPathRepository")
 * @gedmo:Tree(type="materialized_path")
 */
class Category implements NodeInterface
{
    /** @Id */
    private $id;

    /**
     * @String
     */
    private $title;

    /**
     * @gedmo:TreePath
     * @String
     */
    private $path;

    /**
     * @gedmo:TreeChildCount
     * @Int
     */
    private $childCount;

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

    public function getChildCount()
    {
        return $this->childCount;
    }
}
