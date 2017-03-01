<?php

namespace SoftDeleteable\Fixture\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 */
class One
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


    /**
     * @ORM\Column(name="deletedAt", type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @ORM\OneToOne(targetEntity="Two", cascade={"persist"})
     */
    private $two;


    public function getId()
    {
        return $this->id;
    }


    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;
    }

    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    public function setTwo(Two $two)
    {
        $this->two = $two;
    }

    public function getTwo()
    {
        return $this->two;
    }
}
