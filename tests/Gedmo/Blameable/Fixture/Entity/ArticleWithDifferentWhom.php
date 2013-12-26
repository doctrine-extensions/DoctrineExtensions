<?php
namespace Blameable\Fixture\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class ArticleWithDifferentWhom
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    private $id;

    /**
     * @ORM\Column(name="title", type="string", length=128)
     */
    private $title;

    /**
     * @Gedmo\Blameable(on="create")
     * @ORM\Column(type="string")
     */
    private $createdUser;

    /**
     * @Gedmo\Blameable(on="create", whom="consumer")
     * @ORM\Column(type="string")
     */
    private $createdConsumer;

    /**
     * @ORM\Column(type="string")
     * @Gedmo\Blameable(on="update")
     */
    private $updatedUser;

    /**
     * @ORM\Column(type="string")
     * @Gedmo\Blameable(on="update", whom="consumer")
     */
    private $updatedConsumer;

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

    public function getCreatedUser()
    {
        return $this->createdUser;
    }

    public function getCreatedConsumer()
    {
        return $this->createdConsumer;
    }

    public function getUpdatedUser()
    {
        return $this->updatedUser;
    }

    public function getUpdatedConsumer()
    {
        return $this->updatedConsumer;
    }
}