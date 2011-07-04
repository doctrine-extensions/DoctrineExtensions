<?php

namespace Mapping\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Mock\Extension\Encoder\Mapping as Ext;

/**
 * @ORM\Table(name="test_users")
 * @ORM\Entity
 */
class User
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @Ext\Encode(type="sha1", secret="xxx")
     * @ORM\Column(length=64)
     */
    private $name;

    /**
     * @Ext\Encode(type="md5")
     * @ORM\Column(length=32)
     */
    private $password;

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function getPassword()
    {
        return $this->password;
    }
}