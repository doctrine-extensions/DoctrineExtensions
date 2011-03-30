<?php

namespace Sortable\Fixture\Document;

/**
 * @Document(collection="categories")
 */
class Category
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
}
