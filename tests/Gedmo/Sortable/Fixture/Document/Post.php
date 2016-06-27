<?php

namespace Sortable\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Mapping\Annotation as Gedmo;
use Sortable\Fixture\Document\Category;

/**
 * @ODM\Document(collection="posts")
 */
class Post
{
    /** @ODM\Id */
    private $id;

    /**
     * @ODM\Field(type="string")
     */
    private $title;

    /**
     * @Gedmo\SortablePosition
     * @ODM\Field(type="int")
     */
    protected $position;

    /**
     * @Gedmo\SortableGroup
     * @ODM\ReferenceOne(targetDocument="Sortable\Fixture\Document\Category")
     */
    protected $category;

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

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setCategory(Category $category)
    {
        $this->category = $category;
    }

    public function getCategory()
    {
        return $this->category;
    }
}
