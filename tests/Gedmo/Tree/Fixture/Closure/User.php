<?php

namespace Tree\Fixture\Closure;

/**
 * @Entity
 */
class User extends Person
{
    /**
     * @Column(name="username", type="string", length=64)
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
