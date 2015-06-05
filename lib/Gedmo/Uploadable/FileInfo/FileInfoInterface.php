<?php

namespace Gedmo\Uploadable\FileInfo;

/**
 * FileInfoInterface
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

interface FileInfoInterface
{
    public function getTmpName();
    public function getName();
    public function getSize();
    public function getType();
    public function getError();

    /**
     * This method must return true if the file is coming from $_FILES, or false instead.
     *
     * @return bool
     */
    public function isUploadedFile();
}
