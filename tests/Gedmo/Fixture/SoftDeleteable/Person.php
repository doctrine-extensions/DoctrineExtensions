<?php

namespace Gedmo\Fixture\SoftDeleteable;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=true)
 */
class Person
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(length=32)
     */
    private $name;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @ORM\OneToOne(targetEntity="Address", inversedBy="owner", cascade={"remove"})
     */
    private $address;


    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setAddress(Address $address)
    {
        $this->address = $address;
        $address->setOwner($this);
        return $this;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    public function getDeletedAt()
    {
        return $this->deletedAt;
    }
}
