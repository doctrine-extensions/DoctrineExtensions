<?php

namespace Sluggable\Fixture;

/**
 * @Entity
 */
class Comment
{
    /** 
     * @Id 
     * @GeneratedValue 
     * @Column(type="integer") 
     */
    private $id;

    /**
     * @Column(type="text")
     */
    private $message;
    
    /**
     * @ManyToOne(targetEntity="TranslatableArticle", inversedBy="comments")
     */
    private $article;

    public function setArticle(TranslatableArticle $article)
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
}