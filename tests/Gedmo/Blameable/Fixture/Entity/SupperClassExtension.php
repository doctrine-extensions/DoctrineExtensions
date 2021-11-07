<?php

namespace Gedmo\Tests\Blameable\Fixture\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 */
class SupperClassExtension extends MappedSupperClass
{
    /**
     * @ORM\Column(length=128)
     * @Gedmo\Translatable
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
