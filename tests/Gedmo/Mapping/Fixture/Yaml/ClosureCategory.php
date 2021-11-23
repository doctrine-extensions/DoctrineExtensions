<?php

namespace Gedmo\Tests\Mapping\Fixture\Yaml;

use Doctrine\Common\Collections\Collection;

class ClosureCategory
{
    private $id;

    private $title;

    private $children;

    private $parent;

    private $level;

    /**
     * Get id
     *
     * @return int $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Get title
     *
     * @return string $title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Add children
     */
    public function addChildren(Category $children)
    {
        $this->children[] = $children;
    }

    /**
     * Get children
     *
     * @return Collection $children
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set parent
     *
     * @param Category $parent
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * Get parent
     *
     * @return Category $parent
     */
    public function getParent()
    {
        return $this->parent;
    }

    public function setLevel($level)
    {
        $this->level = $level;
    }

    public function getLevel()
    {
        return $this->level;
    }
}
