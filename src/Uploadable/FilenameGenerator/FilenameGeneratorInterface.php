<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Uploadable\FilenameGenerator;

/**
 * FilenameGeneratorInterface
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
interface FilenameGeneratorInterface
{
    /**
     * Generates a new filename
     *
     * @param string      $filename  Filename without extension
     * @param string      $extension Extension with dot: .jpg, .gif, etc
     * @param object|null $object
     *
     * @return string
     */
    public static function generate($filename, $extension, $object = null);
}
