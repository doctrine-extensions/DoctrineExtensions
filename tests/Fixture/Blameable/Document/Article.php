<?php

namespace Fixture\Blameable\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Mapping\Annotation as Gedmo;

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
     * @ODM\String
     */
    private $title;

    /**
     * @ODM\ReferenceOne(targetDocument="Type")
     */
    private $type;

    /**
     * @ODM\String
     * @Gedmo\Blameable
     */
    private $updatedBy;

    /**
     * @ODM\ReferenceOne(targetDocument="User")
     * @Gedmo\Blameable(on="create")
     */
    private $createdBy;

    /**
     * @ODM\String
     * @Gedmo\Blameable(on="change", field="type.title", value="Published")
     */
    private $publishedBy;

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

    public function setType(Type $type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setCreatedBy(User $createdBy)
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    public function setUpdatedBy($updatedBy)
    {
        $this->updatedBy = $updatedBy;
        return $this;
    }

    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }

    public function setPublishedBy($publishedBy)
    {
        $this->publishedBy = $publishedBy;
        return $this;
    }

    public function getPublishedBy()
    {
        return $this->publishedBy;
    }
}
