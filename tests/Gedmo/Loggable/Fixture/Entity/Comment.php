<?php

namespace Loggable\Fixture\Entity;

/**
 * @Entity
 * @gedmo:Loggable(logEntryClass="Loggable\Fixture\Entity\Log\Comment")
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
     * @gedmo:Versioned
     * @Column(length=128)
     */
    private $subject;

    /**
     * @gedmo:Versioned
     * @Column(type="text")
     */
    private $message;

    /**
     * @gedmo:Versioned
     * @ManyToOne(targetEntity="RelatedArticle", inversedBy="comments")
     */
    private $article;

    public function setArticle($article)
    {
        $this->article = $article;
    }

    public function getArticle()
    {
        return $this->article;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    public function getSubject()
    {
        return $this->subject;
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
