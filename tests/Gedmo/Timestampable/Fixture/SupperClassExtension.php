<?php

namespace Timestampable\Fixture;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class SupperClassExtension extends MappedSupperClass
{
    /**
     * @ORM\Column(length=128)
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
