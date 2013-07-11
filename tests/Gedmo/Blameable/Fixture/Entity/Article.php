<?php
namespace Blameable\Fixture\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Article
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    private $id;

    /**
     * @ORM\Column(name="title", type="string", length=128)
     */
    private $title;

    /**
     * @ORM\OneToMany(targetEntity="Blameable\Fixture\Entity\Comment", mappedBy="article")
     */
    private $comments;

    /**
     * @var string $created
     *
     * @Gedmo\Blameable(on="create")
     * @ORM\Column(name="created", type="string")
     */
    private $created;

    /**
     * @var string $updated
     *
     * @ORM\Column(name="updated", type="string")
     * @Gedmo\Blameable
     */
    private $updated;

    /**
     * @var string $published
     *
     * @ORM\Column(name="published", type="string", nullable=true)
     * @Gedmo\Blameable(on="change", field="type.title", value="Published")
     */
    private $published;

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
     * @return string $created
     */
    public function getCreated()
    {
        return $this->created;
    }

    public function setCreated($created)
    {
        $this->created = $created;
    }

    public function getPublished()
    {
        return $this->published;
    }

    public function setPublished($published)
    {
        $this->published = $published;
    }

    /**
     * Get updated
     *
     * @return string $updated
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }
}
