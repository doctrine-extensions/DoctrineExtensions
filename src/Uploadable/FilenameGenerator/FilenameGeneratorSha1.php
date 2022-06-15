<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Uploadable\FilenameGenerator;

/**
 * FilenameGeneratorSha1
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
class FilenameGeneratorSha1 implements FilenameGeneratorInterface
{
    public static function generate($filename, $extension, $object = null)
    {
        return sha1(uniqid($filename.$extension, true)).$extension;
    }
}
