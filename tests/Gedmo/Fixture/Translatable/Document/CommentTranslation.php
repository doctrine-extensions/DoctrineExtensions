<?php

namespace Gedmo\Fixture\Translatable\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoODM;
use Gedmo\Translatable\Document\MappedSuperclass\AbstractTranslation;

/**
 * @MongoODM\Document(collection="comment_translations")
 */
class CommentTranslation extends AbstractTranslation
{
    /**
     * @MongoODM\ReferenceOne(targetDocument="Comment")
     */
    protected $object;

    /**
     * @MongoODM\String
     */
    private $subject;

    /**
     * @MongoODM\String
     */
    private $message;

    public function __construct($locale = null, $subject = null, $message = null)
    {
        $this->locale = $locale;
        $this->subject = $subject;
        $this->message = $message;
    }

    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    public function getMessage()
    {
        return $this->message;
    }
}
