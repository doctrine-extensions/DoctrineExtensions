<?php

namespace Gedmo\Uploadable\FileInfo;

/**
 * These are tests for the FileInfoArray class of the Uploadable behavior
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

class FileInfoArrayTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException RuntimeException
     */
    public function test_constructor_ifKeysAreNotValidOrSomeAreMissingThrowException()
    {
        $fileInfo = new FileInfoArray(array());
    }
}
