<?php

namespace Loggable\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\Document
 * @Gedmo\Loggable(logEntryClass="Loggable\Fixture\Document\Log\Comment")
 */
class Comment
{
    /**
     * @ODM\Id
     */
    private $id;

    /**
     * @Gedmo\Versioned
     * @ODM\String
     */
    private $subject;

    /**
     * @Gedmo\Versioned
     * @ODM\String
     */
    private $message;

    /**
     * @Gedmo\Versioned
     * @ODM\ReferenceOne(targetDocument="RelatedArticle", inversedBy="comments")
     */
    private $article;

    /**
     * @Gedmo\Versioned
     * @ODM\Field(type="base64")
     */
    private $baseString;

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

    public function getBaseString()
    {
        return $this->baseString;
    }

    public function setBaseString($baseString)
    {
        $this->baseString = $baseString;
    }
}
