<?php

namespace Fixture\Tree\Closure;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class User extends Person
{
    /**
     * @ORM\Column(name="username", type="string", length=64)
     */
    private $username;


    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getUsername()
    {
        return $this->username;
    }
}
