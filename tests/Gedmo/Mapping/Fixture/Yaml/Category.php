<?php

namespace Gedmo\Tests\Mapping\Fixture\Yaml;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Category extends BaseCategory
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $slug;

    /**
     * @var Collection<int, Category>
     */
    private $children;

    /**
     * @var Category
     */
    private $parent;

    private $changed;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    /**
     * @return int $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string $title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $slug
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    /**
     * @return string $slug
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param Category $children
     */
    public function addChildren(self $children)
    {
        $this->children[] = $children;
    }

    /**
     * @return Collection $children
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param Category $parent
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * @return Category $parent
     */
    public function getParent()
    {
        return $this->parent;
    }
}
