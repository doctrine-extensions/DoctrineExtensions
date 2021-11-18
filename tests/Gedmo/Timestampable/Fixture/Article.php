<?php

namespace Gedmo\Tests\Timestampable\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Timestampable;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class Article implements Timestampable
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
     * @ORM\Column(name="title", type="string", length=128)
     */
    #[ORM\Column(name: 'title', type: Types::STRING, length: 128)]
    private $title;

    /**
     * @ORM\Column(name="body", type="string")
     */
    #[ORM\Column(name: 'body', type: Types::STRING)]
    private $body;

    /**
     * @ORM\OneToMany(targetEntity="Gedmo\Tests\Timestampable\Fixture\Comment", mappedBy="article")
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'article')]
    private $comments;

    /**
     * @ORM\Embedded(class="Gedmo\Tests\Timestampable\Fixture\Author")
     */
    #[ORM\Embedded(class: Author::class)]
    private $author;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created", type="date")
     */
    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'created', type: Types::DATE_MUTABLE)]
    private $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @Gedmo\Timestampable
     */
    #[ORM\Column(name: 'updated', type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable]
    private $updated;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="published", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="change", field="type.title", value="Published")
     */
    #[ORM\Column(name: 'published', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'change', field: 'type.title', value: 'Published')]
    private $published;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="content_changed", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="change", field={"title", "body"})
     */
    #[ORM\Column(name: 'content_changed', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'change', field: ['title', 'body'])]
    private $contentChanged;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="author_changed", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="change", field={"author.name", "author.email"})
     */
    #[ORM\Column(name: 'author_changed', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'change', field: ['author.name', 'author.email'])]
    private $authorChanged;

    /**
     * @ORM\ManyToOne(targetEntity="Type", inversedBy="articles")
     */
    #[ORM\ManyToOne(targetEntity: Type::class, inversedBy: 'articles')]
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
    }

    public function getBody()
    {
        return $this->body;
    }

    public function addComment(Comment $comment)
    {
        $comment->setArticle($this);
        $this->comments[] = $comment;
    }

    public function getComments()
    {
        return $this->comments;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function setAuthor(Author $author)
    {
        $this->author = $author;
    }

    /**
     * Get created
     *
     * @return \DateTime $created
     */
    public function getCreated()
    {
        return $this->created;
    }

    public function setCreated(\DateTime $created)
    {
        $this->created = $created;
    }

    public function getPublished()
    {
        return $this->published;
    }

    public function setPublished(\DateTime $published)
    {
        $this->published = $published;
    }

    /**
     * Get updated
     *
     * @return \DateTime $updated
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    public function setUpdated(\DateTime $updated)
    {
        $this->updated = $updated;
    }

    public function setContentChanged(\DateTime $contentChanged)
    {
        $this->contentChanged = $contentChanged;
    }

    public function getContentChanged()
    {
        return $this->contentChanged;
    }

    public function getAuthorChanged()
    {
        return $this->authorChanged;
    }
}
