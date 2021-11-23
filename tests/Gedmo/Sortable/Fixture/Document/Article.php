<?php

namespace Gedmo\Tests\Sortable\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\Document(collection="articles")
 */
class Article
{
    /**
     * @Gedmo\SortablePosition
     * @ODM\Field(type="int")
     */
    protected $position;
    /** @ODM\Id */
    private $id;

    /**
     * @ODM\Field(type="string")
     */
    private $title;

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
}
