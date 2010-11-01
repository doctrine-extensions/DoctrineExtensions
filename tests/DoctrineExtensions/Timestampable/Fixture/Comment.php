<?php

namespace Timestampable\Fixture;

use DoctrineExtensions\Timestampable\Timestampable;

/**
 * @Entity
 */
class Comment implements Timestampable
{
    /** @Id @GeneratedValue @Column(type="integer") */
    private $id;

    /**
     * @Column(name="message", type="text")
     */
    private $message;
    
    /**
     * @ManyToOne(targetEntity="Timestampable\Fixture\Article", inversedBy="comments")
     */
    private $article;
    
    /**
     * @var datetime $modified
     *
     * @Column(name="modified", type="time")
     * @Timestampable:OnUpdate
     */
    private $modified;

    public function setArticle($article)
    {
        $this->article = $article;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function getMessage()
    {
        return $this->message;
    }
    
    /**
     * Get modified
     *
     * @return datetime $modified
     */
    public function getModified()
    {
        return $this->modified;
    }
}