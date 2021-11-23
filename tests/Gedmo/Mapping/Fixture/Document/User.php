<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Tests\Mapping\Mock\Extension\Encoder\Mapping as Ext;

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
