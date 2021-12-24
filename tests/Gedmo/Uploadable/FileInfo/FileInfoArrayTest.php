<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Uploadable\FileInfo;

use Gedmo\Uploadable\FileInfo\FileInfoArray;

/**
 * These are tests for the FileInfoArray class of the Uploadable behavior
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class FileInfoArrayTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructorIfKeysAreNotValidOrSomeAreMissingThrowException(): void
    {
        $this->expectException('RuntimeException');
        $fileInfo = new FileInfoArray([]);
    }
}
