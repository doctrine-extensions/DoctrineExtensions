<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Timestampable\Fixture;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Timestampable;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class ArticleCarbon implements Timestampable
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
     * @var string|null
     *
     * @ORM\Column(name="body", type="string")
     */
    #[ORM\Column(name: 'body', type: Types::STRING)]
    private $body;

    /**
     * @var Collection<int, Comment>
     *
     * @ORM\OneToMany(targetEntity="Gedmo\Tests\Timestampable\Fixture\Comment", mappedBy="article")
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'article')]
    private $comments;

    /**
     * @var Author|null
     *
     * @ORM\Embedded(class="Gedmo\Tests\Timestampable\Fixture\Author")
     */
    #[ORM\Embedded(class: Author::class)]
    private $author;

    /**
     * @var \DateTime|\Carbon\Carbon|null
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created", type="date")
     */
    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'created', type: Types::DATE_MUTABLE)]
    private $created;

    /**
     * @var \DateTime|\Carbon\CarbonImmutable|null
     *
     * @ORM\Column(name="updated", type="datetime")
     * @Gedmo\Timestampable
     */
    #[ORM\Column(name: 'updated', type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable]
    private $updated;

    /**
     * @var \DateTime|\Carbon\CarbonImmutable|null
     *
     * @ORM\Column(name="published", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="change", field="type.title", value="Published")
     */
    #[ORM\Column(name: 'published', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'change', field: 'type.title', value: 'Published')]
    private $published;

    /**
     * @var \DateTime|\Carbon\CarbonImmutable|null
     *
     * @ORM\Column(name="content_changed", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="change", field={"title", "body"})
     */
    #[ORM\Column(name: 'content_changed', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'change', field: ['title', 'body'])]
    private $contentChanged;
    /**
     * @var \Carbon\CarbonImmutable|null
     *
     * @ORM\Column(name="author_changed", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="change", field={"author.name", "author.email"})
     */
    #[ORM\Column(name: 'author_changed', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'change', field: ['author.name', 'author.email'])]
    private $authorChanged;

    /**
     * @var Type|null
     *
     * @ORM\ManyToOne(targetEntity="Type", inversedBy="articles")
     */
    #[ORM\ManyToOne(targetEntity: Type::class, inversedBy: 'articles')]
    private $type;

    /**
     * @var int|null
     *
     * @ORM\Column(name="level", type="integer")
     */
    #[ORM\Column(name: 'level', type: Types::INTEGER)]
    private $level = 0;

    /**
     * We use the value "10" as string here in order to check the behavior of `AbstractTrackingListener`
     *
     * @var \DateTimeInterface|null
     *
     * @ORM\Column(name="reached_relevant_level", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="change", field="level", value="10")
     */
    #[ORM\Column(name: 'reached_relevant_level', type: Types::DATE_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'change', field: 'level', value: '10')]
    private $reachedRelevantLevel;

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

    public function setBody(?string $body): void
    {
        $this->body = $body;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function addComment(Comment $comment): void
    {
        $comment->setArticle($this);
        $this->comments[] = $comment;
    }

    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function getAuthor(): ?Author
    {
        return $this->author;
    }

    public function setAuthor(Author $author): void
    {
        $this->author = $author;
    }

    public function getCreated(): ?\Carbon\Carbon
    {
        return $this->created;
    }

    public function setCreated(\DateTime $created): void
    {
        $this->created = $created;
    }

    public function getPublished(): ?\Carbon\CarbonImmutable
    {
        return $this->published;
    }

    public function setPublished(\DateTime $published): void
    {
        $this->published = $published;
    }

    public function getUpdated(): ?\Carbon\CarbonImmutable
    {
        return $this->updated;
    }

    public function setUpdated(\DateTime $updated): void
    {
        $this->updated = $updated;
    }

    public function setContentChanged(\DateTime $contentChanged): void
    {
        $this->contentChanged = $contentChanged;
    }

    public function getContentChanged(): ?\Carbon\CarbonImmutable
    {
        return $this->contentChanged;
    }

    public function getAuthorChanged(): ?\Carbon\CarbonImmutable
    {
        return $this->authorChanged;
    }

    public function setLevel(int $level): void
    {
        $this->level = $level;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getReachedRelevantLevel(): ?\DateTimeInterface
    {
        return $this->reachedRelevantLevel;
    }
}
