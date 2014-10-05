<?php

namespace Translatable\Fixture\Issue922;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Post
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $publishedAt;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(type="time")
     */
    private $timestampAt;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(type="date")
     */
    private $dateAt;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(type="boolean")
     */
    private $boolean;

    public function getId()
    {
        return $this->id;
    }

    public function setPublishedAt($publishedAt)
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    public function getPublishedAt()
    {
        return $this->publishedAt;
    }

    public function setTimestampAt($timestampAt)
    {
        $this->timestampAt = $timestampAt;

        return $this;
    }

    public function getTimestampAt()
    {
        return $this->timestampAt;
    }

    public function setDateAt($dateAt)
    {
        $this->dateAt = $dateAt;

        return $this;
    }

    public function getDateAt()
    {
        return $this->dateAt;
    }

    public function setBoolean($boolean)
    {
        $this->boolean = $boolean;

        return $this;
    }

    public function getBoolean()
    {
        return $this->boolean;
    }
}
