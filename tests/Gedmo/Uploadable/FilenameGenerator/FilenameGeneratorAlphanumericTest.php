<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Uploadable\FilenameGenerator;

use Gedmo\Uploadable\FilenameGenerator\FilenameGeneratorAlphanumeric;
use PHPUnit\Framework\TestCase;

/**
 * These are tests for FilenameGeneratorAlphanumeric class
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class FilenameGeneratorAlphanumericTest extends TestCase
{
    public function testGenerator(): void
    {
        $filename = 'MegaName_For_A_###$$$File$$$###';
        $extension = '.exe';

        static::assertSame('meganame-for-a-file-.exe', FilenameGeneratorAlphanumeric::generate($filename, $extension));
    }
}
