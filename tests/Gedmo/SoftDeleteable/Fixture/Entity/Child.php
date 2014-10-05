<?php

namespace SoftDeleteable\Fixture\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Child extends MappedSuperclass
{
    /**
     * @ORM\Column(name="title", type="string")
     */
    private $title;

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }
}
