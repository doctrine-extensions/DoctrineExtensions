<?php

namespace Translatable\Fixture;

/**
 * @Entity
 */
class Image extends File
{    
    /**
     * @gedmo:Translatable
     * @Column(length=128)
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