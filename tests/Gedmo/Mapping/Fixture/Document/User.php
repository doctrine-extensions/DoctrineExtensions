<?php

namespace Mapping\Fixture\Document;

/**
 * @Document(collection="test_users")
 */
class User
{
    /**
     * @Id
     */
    private $id;

    /**
     * @ext:Encode(type="sha1", secret="xxx")
     * @String
     */
    private $name;

    /**
     * @ext:Encode(type="md5")
     * @String
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