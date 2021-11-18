<?php

namespace Gedmo\Tests\Uploadable;

use Gedmo\Uploadable\FilenameGenerator\FilenameGeneratorAlphanumeric;

/**
 * These are tests for FilenameGeneratorAlphanumeric class
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class FilenameGeneratorAlphanumericTest extends \PHPUnit\Framework\TestCase
{
    public function testGenerator()
    {
        $generator = new FilenameGeneratorAlphanumeric();

        $filename = 'MegaName_For_A_###$$$File$$$###';
        $extension = '.exe';

        static::assertSame('meganame-for-a-file-.exe', $generator->generate($filename, $extension));
    }
}
