<?php

namespace Fixture\Translatable\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoODM;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @MongoODM\Document(collection="posts")
 */
class Post
{
    /**
     * @MongoODM\Id
     */
    private $id;

    /**
     * @Gedmo\Translatable
     * @MongoODM\String
     */
    private $title;

    /**
     * @MongoODM\ReferenceMany(targetDocument="PostTranslation", mappedBy="object", cascade={"all"})
     */
    private $translations;

    /**
     * @MongoODM\ReferenceMany(targetDocument="Comment", mappedBy="post")
     */
    private $comments;

    public function __construct()
    {
        $this->translations = new ArrayCollection;
        $this->comments = new ArrayCollection;
    }

    public function addComment(Comment $comment)
    {
        if (!$this->comments->contains($comment)) {
            $comment->setPost($this);
            $this->comments[] = $comment;
        }
        return $this;
    }

    public function getTranslations()
    {
        return $this->translations;
    }

    public function addTranslation(PostTranslation $t)
    {
        if (!$this->translations->contains($t)) {
            $this->translations[] = $t;
            $t->setObject($this);
        }
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
}
