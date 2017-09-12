<?php

namespace Blameable\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\Document(collection="articles")
 */
class Article
{
    /** @ODM\Id */
    private $id;

    /**
     * @ODM\Field(type="string")
     */
    private $title;

    /**
     * @ODM\ReferenceOne(targetDocument="Type")
     */
    private $type;

    /**
     * @var string $created
     *
     * @ODM\Field(type="string")
     * @Gedmo\Blameable(on="create")
     */
    private $created;

    /**
     * @var string $updated
     *
     * @ODM\Field(type="string")
     * @Gedmo\Blameable
     */
    private $updated;

    /**
     * @ODM\ReferenceOne(targetDocument="User")
     * @Gedmo\Blameable(on="create")
     */
    private $creator;

    /**
     * @var string $published
     *
     * @ODM\Field(type="string")
     * @Gedmo\Blameable(on="change", field="type.title", value="Published")
     */
    private $published;

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

    public function getCreated()
    {
        return $this->created;
    }

    public function getPublished()
    {
        return $this->published;
    }

    public function getCreator()
    {
        return $this->creator;
    }

    public function getUpdated()
    {
        return $this->updated;
    }

    public function setType(Type $type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setCreated($created)
    {
        $this->created = $created;
    }

    public function setPublished($published)
    {
        $this->published = $published;
    }

    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

    public function setCreator($creator)
    {
        $this->creator = $creator;
    }
}
