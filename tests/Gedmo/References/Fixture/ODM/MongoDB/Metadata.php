<?php

namespace Gedmo\Tests\References\Fixture\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tests\References\Fixture\ORM\Category;

/**
 * @ODM\EmbeddedDocument
 * Metadata of type Category
 */
class Metadata
{
    /** @ODM\Field(type="string") */
    private $name;

    /**
     * @Gedmo\ReferenceOne(type="entity", class="Gedmo\Tests\References\Fixture\ORM\Category", identifier="categoryId")
     */
    private $category;

    /** @ODM\Field(type="int") */
    private $categoryId;

    public function __construct($category)
    {
        $this->setCategory($category);
    }

    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;
    }

    public function getCategoryId()
    {
        return $this->categoryId;
    }

    public function setCategory(Category $category)
    {
        $this->category = $category;
        $this->categoryId = $category->getId();
    }

    public function getCategory()
    {
        return $this->category;
    }
}
