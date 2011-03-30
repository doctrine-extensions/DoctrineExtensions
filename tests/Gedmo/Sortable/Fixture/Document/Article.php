<?php

namespace Sortable\Fixture\Document;

/**
 * @Document(collection="articles")
 */
class Article
{
    /** @Id */
    private $id;

    /**
     * @String
     */
    private $title;

    /**
     * @gedmo:Sort
     * @Int
     */
    private $sort;

    /**
     * @ReferenceOne(targetDocument="Category")
     * @gedmo:SortIdentifier
     */
    private $category;
    
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

    public function setSort($sort)
    {
        $this->sort = $sort;
    }

    public function getSort()
    {
        return $this->sort;
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
