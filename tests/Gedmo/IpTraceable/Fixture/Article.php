<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\IpTraceable\Fixture;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\IpTraceable\IpTraceable;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
class Article implements IpTraceable
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: 'title', type: Types::STRING, length: 128)]
    private ?string $title = null;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'article')]
    private Collection $comments;

    #[ORM\Column(name: 'created', type: Types::STRING, length: 45)]
    #[Gedmo\IpTraceable(on: 'create')]
    private ?string $created = null;

    #[ORM\Column(name: 'updated', type: Types::STRING, length: 45)]
    #[Gedmo\IpTraceable]
    private ?string $updated = null;

    #[ORM\Column(name: 'published', type: Types::STRING, length: 45, nullable: true)]
    #[Gedmo\IpTraceable(on: 'change', field: 'type.title', value: 'Published')]
    private ?string $published = null;

    #[ORM\Column(name: 'content_changed', type: Types::STRING, length: 45, nullable: true)]
    #[Gedmo\IpTraceable(on: 'change', field: ['title', 'body'])]
    private ?string $contentChanged = null;

    #[ORM\ManyToOne(targetEntity: Type::class, inversedBy: 'articles')]
    private ?Type $type = null;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }

    public function setType(?Type $type): void
    {
        $this->type = $type;
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

    public function addComment(Comment $comment): void
    {
        $comment->setArticle($this);
        $this->comments[] = $comment;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function getCreated(): ?string
    {
        return $this->created;
    }

    public function setCreated(?string $created): void
    {
        $this->created = $created;
    }

    public function getPublished(): ?string
    {
        return $this->published;
    }

    public function setPublished(?string $published): void
    {
        $this->published = $published;
    }

    public function getUpdated(): ?string
    {
        return $this->updated;
    }

    public function setUpdated(?string $updated): void
    {
        $this->updated = $updated;
    }

    public function setContentChanged(?string $contentChanged): void
    {
        $this->contentChanged = $contentChanged;
    }

    public function getContentChanged(): ?string
    {
        return $this->contentChanged;
    }
}
