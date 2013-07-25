<?php

namespace Fixture\Blameable;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
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
     * @ORM\Column(type="text")
     */
    private $message;

    /**
     * @ORM\ManyToOne(targetEntity="Article")
     */
    private $article;

    /**
     * @ORM\Column(type="integer")
     */
    private $status;

    /**
     * @ORM\Column(nullable=true)
     * @Gedmo\Blameable(on="change", field="status", value=1)
     */
    private $closedBy;

    /**
     * @ORM\Column
     * @Gedmo\Blameable(on="update")
     */
    private $modifiedBy;

    public function setArticle(Article $article)
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

    public function setClosedBy($closedBy)
    {
        $this->closedBy = $closedBy;
        return $this;
    }

    public function getClosedBy()
    {
        return $this->closedBy;
    }

    public function setModifiedBy($modifiedBy)
    {
        $this->modifiedBy = $modifiedBy;
        return $this;
    }

    public function getModifiedBy()
    {
        return $this->modifiedBy;
    }
}
