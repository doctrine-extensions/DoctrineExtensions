<?php

namespace Fixture\Translatable;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * guess translation class
 */
class Comment
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(length=128)
     */
    private $subject;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(type="text")
     */
    private $message;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(type="integer")
     */
    private $rating = 0;

    /**
     * @ORM\ManyToOne(targetEntity="Post", inversedBy="comments")
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

    public function setRating($rating)
    {
        $this->rating = $rating;
        return $this;
    }

    public function getRating()
    {
        return $this->rating;
    }
}
