<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\IpTraceable\Fixture;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\IpTraceable\IpTraceable;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class Article implements IpTraceable
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
     * @var string|null
     *
     * @ORM\Column(name="title", type="string", length=128)
     */
    #[ORM\Column(name: 'title', type: Types::STRING, length: 128)]
    private $title;

    /**
     * @var Collection<int, Comment>
     *
     * @ORM\OneToMany(targetEntity="Gedmo\Tests\IpTraceable\Fixture\Comment", mappedBy="article")
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'article')]
    private $comments;

    /**
     * @var string|null
     *
     * @Gedmo\IpTraceable(on="create")
     * @ORM\Column(name="created", type="string", length=45)
     */
    #[ORM\Column(name: 'created', type: Types::STRING, length: 45)]
    #[Gedmo\IpTraceable(on: 'create')]
    private $created;

    /**
     * @var string|null
     *
     * @ORM\Column(name="updated", type="string", length=45)
     * @Gedmo\IpTraceable
     */
    #[ORM\Column(name: 'updated', type: Types::STRING, length: 45)]
    #[Gedmo\IpTraceable]
    private $updated;

    /**
     * @var string|null
     *
     * @ORM\Column(name="published", type="string", length=45, nullable=true)
     * @Gedmo\IpTraceable(on="change", field="type.title", value="Published")
     */
    #[ORM\Column(name: 'published', type: Types::STRING, length: 45, nullable: true)]
    #[Gedmo\IpTraceable(on: 'change', field: 'type.title', value: 'Published')]
    private $published;

    /**
     * @var string|null
     *
     * @ORM\Column(name="content_changed", type="string", length=45, nullable=true)
     * @Gedmo\IpTraceable(on="change", field={"title", "body"})
     */
    #[ORM\Column(name: 'content_changed', type: Types::STRING, length: 45, nullable: true)]
    #[Gedmo\IpTraceable(on: 'change', field: ['title', 'body'])]
    private $contentChanged;

    /**
     * @var Type|null
     *
     * @ORM\ManyToOne(targetEntity="Type", inversedBy="articles")
     */
    #[ORM\ManyToOne(targetEntity: Type::class, inversedBy: 'articles')]
    private $type;

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
