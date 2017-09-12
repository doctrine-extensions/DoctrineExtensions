<?php

namespace ReferenceIntegrity\Fixture\Document\OnePull;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="articles")
 */
class Article
{
    /**
     * @ODM\Id
     */
    private $id;

    /**
     * @ODM\Field(type="string")
     */
    private $title;

    /**
     * @ODM\ReferenceMany(targetDocument="Type", simple="true", inversedBy="articles")
     * @var ArrayCollection
     */
    private $types;
    
    public function __construct()
    {
        $this->types = new ArrayCollection();
    }

    /**
     * @return mixed
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
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Add types
     *
     * @param Type $type
     */
    public function addType(Type $type)
    {
        $this->types[] = $type;
    }

    /**
     * Get posts
     *
     * @return ArrayCollection $types
     */
    public function getTypes()
    {
        return $this->types;
    }
}
