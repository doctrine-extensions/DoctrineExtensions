<?php

namespace Gedmo\Fixture\Translatable\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\Document
 */
class Comment
{
    /**
     * @ODM\Id
     */
    private $id;

    /**
     * @Gedmo\Translatable
     * @ODM\String
     */
    private $subject;

    /**
     * @Gedmo\Translatable
     * @ODM\String
     */
    private $message;

    /**
     * @ODM\ReferenceOne(targetDocument="Post", inversedBy="comments")
     */
    private $post;

    public function setPost(Post $post)
    {
        $this->post = $post;
        return $this;
    }

    public function getPost()
    {
        return $this->post;
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
