<?php

namespace Loggable\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\Document(collection="articles")
 * @Gedmo\Loggable
 */
class Article
{
    /** @ODM\Id */
    private $id;

    /**
     * @Gedmo\Versioned
     * @ODM\String
     */
    private $title;

    /**
     * @Gedmo\Versioned
     * @ODM\EmbedOne(targetDocument="User")
     */
    private $author;

    /**
     * @Gedmo\Versioned
     * @ODM\EmbedMany(targetDocument="EmbeddedComment")
     */
    private $comments;

    public function __toString()
    {
        return $this->title;
    }

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

    public function getAuthor()
    {
        return $this->author;
    }

    public function setAuthor(User $author = null)
    {
        $this->author = $author;
    }

    public function addComment(EmbeddedComment $comment)
    {
        $this->comments[] = $comment;
    }

    public function getComments()
    {
        return $this->comments;
    }

    public function setComments($comments)
    {
        $this->comments = $comments;
    }
}
