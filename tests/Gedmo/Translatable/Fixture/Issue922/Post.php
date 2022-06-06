<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
     * @var int|null
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    /**
     * @var \DateTime|null
     *
     * @Gedmo\Translatable
     * @ORM\Column(type="datetime", nullable=true)
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Translatable]
    private $publishedAt;

    /**
     * @var \DateTime|null
     *
     * @Gedmo\Translatable
     * @ORM\Column(type="time")
     */
    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Gedmo\Translatable]
    private $timestampAt;

    /**
     * @var \DateTime|null
     *
     * @Gedmo\Translatable
     * @ORM\Column(type="date")
     */
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Gedmo\Translatable]
    private $dateAt;

    /**
     * @var bool|null
     *
     * @Gedmo\Translatable
     * @ORM\Column(type="boolean")
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    #[Gedmo\Translatable]
    private $boolean;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setPublishedAt(?\DateTime $publishedAt): self
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    public function getPublishedAt(): ?\DateTime
    {
        return $this->publishedAt;
    }

    public function setTimestampAt(?\DateTime $timestampAt): self
    {
        $this->timestampAt = $timestampAt;

        return $this;
    }

    public function getTimestampAt(): ?\DateTime
    {
        return $this->timestampAt;
    }

    public function setDateAt(?\DateTime $dateAt): self
    {
        $this->dateAt = $dateAt;

        return $this;
    }

    public function getDateAt(): ?\DateTime
    {
        return $this->dateAt;
    }

    public function setBoolean(bool $boolean): self
    {
        $this->boolean = $boolean;

        return $this;
    }

    public function getBoolean(): ?bool
    {
        return $this->boolean;
    }
}
