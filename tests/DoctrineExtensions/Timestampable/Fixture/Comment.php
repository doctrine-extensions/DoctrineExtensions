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
     * @Column(type="integer")
     */
    private $status;
    
    /**
     * @var datetime $closed
     *
     * @Column(name="closed", type="datetime", nullable=true)
     * @Timestampable:OnChange(field="status", value=1)
     */
    private $closed;
    
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

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getStatus()
    {
        return $this->status;
    }
    
    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function getMessage()
    {
        return $this->message;
    }
    
    public function getModified()
    {
        return $this->modified;
    }
    
    public function getClosed()
    {
        return $this->closed;
    }
}