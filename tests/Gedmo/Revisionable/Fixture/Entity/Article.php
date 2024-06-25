<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Revisionable\Fixture\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Revisionable\Revisionable;

/**
 * @ORM\Entity
 *
 * @Gedmo\Revisionable
 */
#[ORM\Entity]
#[Gedmo\Revisionable]
class Article implements Revisionable
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    /**
     * @ORM\Column(name="title", type="string", length=8)
     *
     * @Gedmo\Versioned
     */
    #[ORM\Column(name: 'title', type: Types::STRING, length: 8)]
    #[Gedmo\Versioned]
    private ?string $title = null;

    /**
     * @ORM\Column(name="publish_at", type="datetime_immutable")
     *
     * @Gedmo\Versioned
     */
    #[ORM\Column(name: 'publish_at', type: Types::DATETIME_IMMUTABLE)]
    #[Gedmo\Versioned]
    private ?\DateTimeImmutable $publishAt = null;

    /**
     * @ORM\Embedded(class="Gedmo\Tests\Revisionable\Fixture\Entity\Author")
     *
     * @Gedmo\Versioned
     */
    #[ORM\Embedded(class: Author::class)]
    #[Gedmo\Versioned]
    private ?Author $author = null;

    public function __toString()
    {
        return $this->title;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setPublishAt(?\DateTimeImmutable $publishAt): void
    {
        $this->publishAt = $publishAt;
    }

    public function getPublishAt(): ?\DateTimeImmutable
    {
        return $this->publishAt;
    }

    public function setAuthor(?Author $author): void
    {
        $this->author = $author;
    }

    public function getAuthor(): ?Author
    {
        return $this->author;
    }
}
