<?php

namespace Tree\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MONGO;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @MONGO\Document(repositoryClass="Gedmo\Tree\Document\MongoDB\Repository\MaterializedPathRepository")
 * @Gedmo\Tree(type="materializedPath")
 */
class CategoryWithAppendIdOnIntegerSource
{
    /**
     * @MONGO\Id
     */
    private $id;

    /**
     * @MONGO\Field(type="string")
     */
    private $title;

    /**
     * @MONGO\Field(type="int")
     * @Gedmo\TreePathSource
     */
    private $integerSort;

    /**
     * @MONGO\Field(type="string")
     * @Gedmo\TreePath(appendId=true, separator="|")
     */
    private $path;

    /**
     * @Gedmo\TreeParent
     * @MONGO\ReferenceOne(targetDocument="Tree\Fixture\Document\CategoryWithAppendIdOnIntegerSource")
     */
    private $parent;

    /**
     * @Gedmo\TreeLevel
     * @MONGO\Field(type="int")
     */
    private $level;

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

    public function setIntegerSort(int $sort)
    {
        $this->integerSort = $sort;
    }

    public function getIntegerSort()
    {
        return $this->integerSort;
    }

    public function setParent(CategoryWithAppendIdOnIntegerSource $parent = null)
    {
        $this->parent = $parent;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function getPath()
    {
        return $this->path;
    }
}
