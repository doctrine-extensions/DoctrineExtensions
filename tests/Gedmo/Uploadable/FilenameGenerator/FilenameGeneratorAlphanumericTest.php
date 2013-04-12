<?php

namespace Gedmo\Uploadable;

use Gedmo\Uploadable\FilenameGenerator\FilenameGeneratorAlphanumeric;

/**
 * These are tests for FilenameGeneratorAlphanumeric class
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class FilenameGeneratorAlphanumericTest extends \PHPUnit_Framework_TestCase
{
    public function testGenerator()
    {
        $generator = new FilenameGeneratorAlphanumeric();

        $filename = 'MegaName_For_A_###$$$File$$$###';
        $extension = '.exe';

        $this->assertEquals('meganame-for-a-file-.exe', $generator->generate($filename, $extension));
    }
}
