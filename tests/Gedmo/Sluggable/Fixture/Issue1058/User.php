<?php

namespace Sluggable\Fixture\Issue1058;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

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
