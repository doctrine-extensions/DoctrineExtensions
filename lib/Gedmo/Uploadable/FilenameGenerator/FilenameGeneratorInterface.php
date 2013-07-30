<?php

namespace Gedmo\Uploadable\FilenameGenerator;

/**
 * FilenameGeneratorInterface
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface FilenameGeneratorInterface
{
    /**
     * Generates a new filename
     *
     * @param string - Filename without extension
     * @param string - Extension with dot: .jpg, .gif, etc
     * @param $object
     *
     * @return string
     */
    public static function generate($filename, $extension, $object = null);
}
