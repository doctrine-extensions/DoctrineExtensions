<?php

namespace Loggable\Fixture\Document;

/**
 * @Document
 * @gedmo:Loggable(logEntryClass="Loggable\Fixture\Document\Log\Comment")
 */
class Comment
{
    /**
     * @Id
     */
    private $id;

    /**
     * @gedmo:Versioned
     * @String
     */
    private $subject;

    /**
     * @gedmo:Versioned
     * @String
     */
    private $message;

    /**
     * @gedmo:Versioned
     * @ReferenceOne(targetDocument="RelatedArticle")
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
