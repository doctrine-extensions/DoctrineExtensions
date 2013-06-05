<?php
namespace Timestampable\Fixture;

use Gedmo\Timestampable\Timestampable;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Article implements Timestampable
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    private $id;

    /**
     * @ORM\Column(name="title", type="string", length=128)
     */
    private $title;

    /**
     * @ORM\Column(name="body", type="string")
     */
    private $body;

    /**
     * @ORM\OneToMany(targetEntity="Timestampable\Fixture\Comment", mappedBy="article")
     */
    private $comments;

    /**
     * @var datetime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created", type="date")
     */
    private $created;

    /**
     * @var datetime $updated
     *
     * @ORM\Column(name="updated", type="datetime")
     * @Gedmo\Timestampable
     */
    private $updated;

    /**
     * @var datetime $published
     *
     * @ORM\Column(name="published", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="change", field="type.title", value="Published")
     */
    private $published;

    /**
     * @var datetime $contentChanged
     *
     * @ORM\Column(name="content_changed", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="change", field={"title", "body"})
     */
    private $contentChanged;

    /**
     * @ORM\ManyToOne(targetEntity="Type", inversedBy="articles")
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

    /**
     * Get created
     *
     * @return datetime $created
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
     * @return datetime $updated
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

}