<?php

namespace Gedmo\Uploadable\Stub;

use Gedmo\Uploadable\MimeType\MimeTypeGuesserInterface;

class MimeTypeGuesserStub implements MimeTypeGuesserInterface
{
    protected $mimeType;

    public function __construct($mimeType)
    {
        $this->mimeType = $mimeType;
    }

    public function guess($path)
    {
        return $this->mimeType;
    }
}
