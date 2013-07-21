<?php

namespace References\Fixture\ODM\MongoDB;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Mapping\Annotation as Gedmo;
use References\Fixture\ORM\Category;

/**
 * @ODM\EmbeddedDocument
 * Metadata of type Category
 */
class Metadata
{
    /** @ODM\Field(type="string") */
    private $name;

    /**
     * @Gedmo\ReferenceOne(type="entity", class="References\Fixture\ORM\Category", identifier="categoryId")
     */
    private $category;

    /** @ODM\Field(type="int") */
    private $categoryId;

    function __construct($category) {
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
