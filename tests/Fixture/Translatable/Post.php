<?php

namespace Fixture\Translatable;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 */
class Post
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(length=64)
     */
    private $title;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(type="text", nullable=true)
     */
    private $content;

    /**
     * @ORM\Column(name="deletedAt", type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @ORM\OneToMany(targetEntity="PostTranslation", mappedBy="object", cascade={"ALL"})
     */
    private $translations;

    /**
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="post")
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

    public function getComments()
    {
        return $this->comments;
    }

    public function getTranslations()
    {
        return $this->translations;
    }

    public function addTranslation(PostTranslation $translation)
    {
        if (!$this->translations->contains($translation)) {
            $this->translations[] = $translation;
            $translation->setObject($this);
        }
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;
    }
}
