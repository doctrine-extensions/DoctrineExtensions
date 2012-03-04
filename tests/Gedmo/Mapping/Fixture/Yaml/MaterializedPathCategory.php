<?php

namespace Mapping\Fixture\Yaml;

class MaterializedPathCategory
{
    private $id;

    private $title;

    private $path;

    private $level;

    private $children;

    private $parent;

    private $lockTime;

    /**
     * Get id
     *
     * @return integer $id
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
     *
     * @param Entity\Category $children
     */
    public function addChildren(Category $children)
    {
        $this->children[] = $children;
    }

    /**
     * Get children
     *
     * @return Doctrine\Common\Collections\Collection $children
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set parent
     *
     * @param Entity\Category $parent
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * Get parent
     *
     * @return Entity\Category $parent
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

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setLockTime($lockTime)
    {
        $this->lockTime = $lockTime;
    }

    public function getLockTime()
    {
        return $this->lockTime;
    }
}