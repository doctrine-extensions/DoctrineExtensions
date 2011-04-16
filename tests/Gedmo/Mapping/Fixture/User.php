<?php

namespace Mapping\Fixture;

/**
 * @Table(name="test_users")
 * @Entity
 */
class User
{
    /**
     * @Column(type="integer")
     * @Id
     * @GeneratedValue
     */
    private $id;

    /**
     * @ext:Encode(type="sha1", secret="xxx")
     * @Column(length=64)
     */
    private $name;

    /**
     * @ext:Encode(type="md5")
     * @Column(length=32)
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