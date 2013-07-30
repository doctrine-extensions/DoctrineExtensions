<?php

namespace Fixture\Uploadable\Fake;

use Gedmo\Uploadable\FilenameGenerator\FilenameGeneratorInterface;

class FileGenerator implements FilenameGeneratorInterface
{
    public static function generate($filename, $extension, $object = null)
    {
        return '123.txt';
    }
}
