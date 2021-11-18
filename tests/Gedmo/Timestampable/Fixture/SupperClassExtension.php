<?php

namespace Gedmo\Tests\Timestampable\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class SupperClassExtension extends MappedSupperClass
{
    /**
     * @ORM\Column(length=128)
     * @Gedmo\Translatable
     */
    #[ORM\Column(length: 128)]
    #[Gedmo\Translatable]
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
