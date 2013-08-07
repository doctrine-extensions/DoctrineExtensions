<?php

namespace Gedmo\Fixture\Loggable;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @Gedmo\Loggable(logEntryClass="Gedmo\Fixture\Loggable\Log\Comment")
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
     * @Gedmo\Versioned
     * @ORM\Column(length=128)
     */
    private $subject;

    /**
     * @Gedmo\Versioned
     * @ORM\Column(type="text")
     */
    private $message;

    /**
     * @Gedmo\Versioned
     * @ORM\ManyToOne(targetEntity="RelatedArticle", inversedBy="comments")
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
