<?php

namespace Gedmo\Uploadable\FileInfo;

/**
 * FileDelete
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

class FileDelete implements FileInfoInterface
{

    public function getTmpName()
    {
        return null;
    }

    public function getName()
    {
        return null;
    }

    public function getSize()
    {
        return null;
    }

    public function getType()
    {
        return null;
    }

    public function getError()
    {
        return null;
    }

    public function isUploadedFile()
    {
        return null;
    }
}
