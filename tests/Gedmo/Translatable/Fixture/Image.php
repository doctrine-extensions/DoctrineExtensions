<?php

namespace Gedmo\Tests\Translatable\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class Image extends File
{
    /**
     * @Gedmo\Translatable
     * @ORM\Column(length=128)
     */
    #[Gedmo\Translatable]
    #[ORM\Column(length: 128)]
    private $mime;

    public function setMime($mime)
    {
        $this->mime = $mime;
    }

    public function getMime()
    {
        return $this->mime;
    }
}
