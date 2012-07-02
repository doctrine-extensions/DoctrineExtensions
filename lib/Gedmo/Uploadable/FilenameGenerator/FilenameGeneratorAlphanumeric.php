<?php

namespace Gedmo\Uploadable\FilenameGenerator;

/**
 * FilenameGeneratorAlphanumeric
 *
 * This class generates a filename, leaving only lowercase
 * alphanumeric characters
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Uploadable.FilenameGenerator
 * @subpackage FilenameGeneratorAlphanumeric
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

class FilenameGeneratorAlphanumeric implements FilenameGeneratorInterface
{
    /**
     * @inheritDoc
     */
    public static function generate($filename, $extension)
    {
        return preg_replace('/[^a-z0-9]+/', '-', strtolower($filename)).$extension;
    }
}
