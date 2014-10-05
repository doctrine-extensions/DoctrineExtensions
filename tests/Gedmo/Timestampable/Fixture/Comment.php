<?php

namespace Timestampable\Fixture;

use Gedmo\Timestampable\Timestampable;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Comment implements Timestampable
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    private $id;

    /**
     * @ORM\Column(name="message", type="text")
     */
    private $message;

    /**
     * @ORM\ManyToOne(targetEntity="Timestampable\Fixture\Article", inversedBy="comments")
     */
    private $article;

    /**
     * @ORM\Column(type="integer")
     */
    private $status;

    /**
     * @var datetime $closed
     *
     * @ORM\Column(name="closed", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="change", field="status", value=1)
     */
    private $closed;

    /**
     * @var datetime $modified
     *
     * @ORM\Column(name="modified", type="time")
     * @Gedmo\Timestampable(on="update")
     */
    private $modified;

    public function setArticle($article)
    {
        $this->article = $article;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getModified()
    {
        return $this->modified;
    }

    public function getClosed()
    {
        return $this->closed;
    }
}
