<?php

namespace Fixture\Blameable;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Article
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(length=32)
     */
    private $title;

    /**
     * @ORM\Column(type="text")
     */
    private $body;

    /**
     * @Gedmo\Blameable(on="create")
     * @ORM\Column
     */
    private $createdBy;

    /**
     * @ORM\Column
     * @Gedmo\Blameable
     */
    private $updatedBy;

    /**
     * @ORM\Column(nullable=true)
     * @Gedmo\Blameable(on="change", field="type.title", value="Published")
     */
    private $publishedBy;

    /**
     * @ORM\Column(nullable=true)
     * @Gedmo\Blameable(on="change", field={"title", "body"})
     */
    private $changedBy;

    /**
     * @ORM\ManyToOne(targetEntity="Type")
     */
    private $type;

    public function setType($type)
    {
        $this->type = $type;
    }

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

    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function setCreatedBy($createdBy)
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

    public function setChangedBy($changedBy)
    {
        $this->changedBy = $changedBy;
        return $this;
    }

    public function getChangedBy()
    {
        return $this->changedBy;
    }
}
