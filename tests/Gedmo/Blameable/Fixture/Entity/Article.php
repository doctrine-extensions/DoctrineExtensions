<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Blameable\Fixture\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Blameable;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;

/**
 * @ORM\Entity
 *
 * @Gedmo\SoftDeleteable
 */
#[ORM\Entity]
#[Gedmo\SoftDeleteable]
class Article implements Blameable
{
    use SoftDeleteableEntity;

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
     * @ORM\Column(name="title", type="string", length=128, nullable=true)
     */
    #[ORM\Column(name: 'title', type: Types::STRING, length: 128, nullable: true)]
    private ?string $title = null;

    /**
     * @var Collection<int, Comment>
     *
     * @ORM\OneToMany(targetEntity="Gedmo\Tests\Blameable\Fixture\Entity\Comment", mappedBy="article")
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'article')]
    private $comments;

    /**
     * @ORM\Column(name="created", type="string", nullable=true)
     *
     * @Gedmo\Blameable(on="create")
     */
    #[ORM\Column(name: 'created', type: Types::STRING, nullable: true)]
    #[Gedmo\Blameable(on: 'create')]
    private ?string $created = null;

    /**
     * @ORM\Column(name="updated", type="string", nullable=true)
     *
     * @Gedmo\Blameable(on="update")
     */
    #[Gedmo\Blameable(on: 'update')]
    #[ORM\Column(name: 'updated', type: Types::STRING, nullable: true)]
    private ?string $updated = null;

    /**
     * @ORM\Column(name="deleted_by", type="string", nullable=true)
     *
     * @Gedmo\Blameable(on="remove")
     */
    #[Gedmo\Blameable(on: 'remove')]
    #[ORM\Column(name: 'deleted_by', type: Types::STRING, nullable: true)]
    private ?string $deletedBy = null;

    /**
     * @ORM\Column(name="published", type="string", nullable=true)
     *
     * @Gedmo\Blameable(on="change", field="type.title", value="Published")
     */
    #[ORM\Column(name: 'published', type: Types::STRING, nullable: true)]
    #[Gedmo\Blameable(on: 'change', field: 'type.title', value: 'Published')]
    private ?string $published = null;

    /**
     * @ORM\ManyToOne(targetEntity="Type", inversedBy="articles")
     */
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

    public function getDeletedBy(): ?string
    {
        return $this->deletedBy;
    }

    public function setDeletedBy(?string $deletedBy): void
    {
        $this->deletedBy = $deletedBy;
    }
}
