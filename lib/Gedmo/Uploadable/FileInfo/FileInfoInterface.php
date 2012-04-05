<?php

namespace Gedmo\Uploadable\FileInfo;

/**
 * FileInfoInterface
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Uploadable.FileInfo
 * @subpackage FileInfoInterface
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface FileInfoInterface
{
    public function getTmpName();
    public function getName();
    public function getSize();
    public function getType();
    public function getError();
}
