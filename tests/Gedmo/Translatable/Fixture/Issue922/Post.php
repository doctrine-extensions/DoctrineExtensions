<?php

namespace Gedmo\Tests\Translatable\Fixture\Issue922;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class Post
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(type="datetime", nullable=true)
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Translatable]
    private $publishedAt;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(type="time")
     */
    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Gedmo\Translatable]
    private $timestampAt;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(type="date")
     */
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Gedmo\Translatable]
    private $dateAt;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(type="boolean")
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    #[Gedmo\Translatable]
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
