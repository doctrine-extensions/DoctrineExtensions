<?php

namespace Gedmo\Fixture\Timestampable\Superclassed;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Person extends Trackable
{
    /**
     * @ORM\Column(length=128)
     */
    private $surname;

    public function setSurname($surname)
    {
        $this->surname = $surname;
        return $this;
    }

    public function getSurname()
    {
        return $this->surname;
    }
}
