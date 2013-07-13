<?php

namespace Fixture\Unmapped;

use Gedmo\Mapping\Annotation\Timestampable;
use Fixture\EncoderExtension\Mapping\Encode;

class Person extends Nameable
{
    private $id;

    protected $surname;

    private $address;

    private $bio;

    /**
     * used for custom mapping driver test
     * @Timestampable(on="create")
     */
    private $createdAt;

    private $updatedAt;

    /**
     * @Encode(type="sha1", secret="guess")
     */
    private $password;

    public function getId()
    {
        return $this->id;
    }

    public function setSurname($surname)
    {
        $this->surname = $surname;
        return $this;
    }

    public function getSurname()
    {
        return $this->surname;
    }

    public function setAddress(Address $address)
    {
        $this->address = $address;
        return $this;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function setBio($bio)
    {
        $this->bio = $bio;
        return $this;
    }

    public function getBio()
    {
        return $this->bio;
    }

    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    public function getPassword()
    {
        return $this->password;
    }
}
