<?php

namespace Translatable\Fixture;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Image extends File
{
    /**
     * @Gedmo\Translatable
     * @ORM\Column(length=128)
     */
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
