<?php

namespace Gedmo\Tests\Sluggable\Fixture\Issue1058;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class User
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * Getter of Id
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }
}
