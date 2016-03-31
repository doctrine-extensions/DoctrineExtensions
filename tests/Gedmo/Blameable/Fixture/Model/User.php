<?php

namespace Blameable\Fixture\Model;

/**
 * A POPO representation of a user, used in hybrid test, to avoid initializing both ORM and ODM sides
 *
 * @see Gedmo\Blameable\BlameableHybridTest
 */
class User
{
    private $id;
    private $username;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param mixed $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }
}
