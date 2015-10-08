<?php

namespace Translatable\Fixture\Issue;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class Staff
 * @ORM\Entity
 */
class Staff extends Person
{

    /**
     * @var string
     * @Gedmo\Translatable
     * @ORM\Column(name="role", type="string", length=128)
     */
    protected $role;

    /**
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param string $role
     *
     * @return $this
     */
    public function setRole($role)
    {
        $this->role = $role;

        return $this;
    }


}