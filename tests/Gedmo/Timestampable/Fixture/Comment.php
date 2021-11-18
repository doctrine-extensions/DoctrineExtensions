<?php

namespace Gedmo\Tests\Timestampable\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Timestampable;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class Comment implements Timestampable
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    /**
     * @ORM\Column(name="message", type="text")
     */
    #[ORM\Column(name: 'message', type: Types::TEXT)]
    private $message;

    /**
     * @ORM\ManyToOne(targetEntity="Gedmo\Tests\Timestampable\Fixture\Article", inversedBy="comments")
     */
    #[ORM\ManyToOne(targetEntity: Article::class, inversedBy: 'comments')]
    private $article;

    /**
     * @ORM\Column(type="integer")
     */
    #[ORM\Column(type: Types::INTEGER)]
    private $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="closed", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="change", field="status", value=1)
     */
    #[ORM\Column(name: 'closed', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'change', field: 'status', value: 1)]
    private $closed;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modified", type="time")
     * @Gedmo\Timestampable(on="update")
     */
    #[ORM\Column(name: 'modified', type: Types::TIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'update')]
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
