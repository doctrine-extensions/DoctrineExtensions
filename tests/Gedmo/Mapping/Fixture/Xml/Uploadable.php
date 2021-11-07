<?php

namespace Gedmo\Tests\Mapping\Fixture\Xml;

class Uploadable
{
    private $id;

    private $mimeType;

    private $fileInfo;

    private $size;

    private $path;

    public function getPath()
    {
        return $this->path;
    }

    public function callbackMethod()
    {
    }
}
