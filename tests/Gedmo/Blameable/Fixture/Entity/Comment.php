<?php

namespace Blameable\Fixture\Entity;

use Gedmo\Blameable\Blameable;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Comment implements Blameable
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    private $id;

    /**
     * @ORM\Column(name="message", type="text")
     */
    private $message;

    /**
     * @ORM\ManyToOne(targetEntity="Blameable\Fixture\Entity\Article", inversedBy="comments")
     */
    private $article;

    /**
     * @ORM\Column(type="integer")
     */
    private $status;

    /**
     * @var string $closed
     *
     * @ORM\Column(name="closed", type="string", nullable=true)
     * @Gedmo\Blameable(on="change", field="status", value=1)
     */
    private $closed;

    /**
     * @var string $modified
     *
     * @ORM\Column(name="modified", type="string")
     * @Gedmo\Blameable(on="update")
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
