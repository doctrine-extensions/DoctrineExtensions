<?php

namespace Mapping\Fixture\Document;

use Gedmo\Mapping\Mock\Extension\Encoder\Mapping as Ext;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="test_users")
 */
class User
{
    /**
     * @ODM\Id
     */
    private $id;

    /**
     * @Ext\Encode(type="sha1", secret="xxx")
     * @ODM\Field(type="string")
     */
    private $name;

    /**
     * @Ext\Encode(type="md5")
     * @ODM\Field(type="string")
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
