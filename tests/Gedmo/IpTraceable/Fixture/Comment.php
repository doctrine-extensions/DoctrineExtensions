<?php

namespace IpTraceable\Fixture;

use Gedmo\IpTraceable\IpTraceable;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Comment implements IpTraceable
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    private $id;

    /**
     * @ORM\Column(name="message", type="text")
     */
    private $message;

    /**
     * @ORM\ManyToOne(targetEntity="IpTraceable\Fixture\Article", inversedBy="comments")
     */
    private $article;

    /**
     * @ORM\Column(type="integer")
     */
    private $status;

    /**
     * @var string $closed
     *
     * @ORM\Column(name="closed", type="string", length=45, nullable=true)
     * @Gedmo\IpTraceable(on="change", field="status", value=1)
     */
    private $closed;

    /**
     * @var string $modified
     *
     * @ORM\Column(name="modified", type="string", length=45)
     * @Gedmo\IpTraceable(on="update")
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