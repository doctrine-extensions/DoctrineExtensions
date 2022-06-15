<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Uploadable\FilenameGenerator;

/**
 * FilenameGeneratorAlphanumeric
 *
 * This class generates a filename, leaving only lowercase
 * alphanumeric characters
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
class FilenameGeneratorAlphanumeric implements FilenameGeneratorInterface
{
    public static function generate($filename, $extension, $object = null)
    {
        return preg_replace('/[^a-z0-9]+/', '-', strtolower($filename)).$extension;
    }
}
