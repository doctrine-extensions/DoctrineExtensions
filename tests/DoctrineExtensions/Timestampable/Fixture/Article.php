<?php
namespace Timestampable\Fixture;

use DoctrineExtensions\Timestampable\Timestampable;

/**
 * @Entity
 */
class Article implements Timestampable
{
    /** @Id @GeneratedValue @Column(type="integer") */
    private $id;

    /**
     * @Column(name="title", type="string", length=128)
     */
    private $title;
    
    /**
     * @OneToMany(targetEntity="Timestampable\Fixture\Comment", mappedBy="article")
     */
    private $comments;
    
    /**
     * @var datetime $created
     *
     * @Column(name="created", type="date")
     */
    private $created;
    
    /**
     * @var datetime $updated
     *
     * @Column(name="updated", type="datetime")
     */
    private $updated;

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
     * @return datetime $created
     */
    public function getCreated()
    {
        return $this->created;
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
}