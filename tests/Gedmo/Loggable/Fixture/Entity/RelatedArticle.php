<?php

namespace Loggable\Fixture\Entity;

/**
 * @Entity
 * @gedmo:Loggable
 */
class RelatedArticle
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    private $id;

    /**
     * @gedmo:Versioned
     * @Column(length=128)
     */
    private $title;

    /**
     * @gedmo:Versioned
     * @Column(type="text")
     */
    private $content;

    /**
     * @OneToMany(targetEntity="Comment", mappedBy="article")
     */
    private $comments;

    public function getId()
    {
        return $this->id;
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

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }
}