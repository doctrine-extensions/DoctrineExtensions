<?php

namespace Gedmo\Uploadable\Stub;

use Gedmo\Uploadable\UploadableListener;

/**
 * UploadableListenerStub
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Uploadable.Stub
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

class UploadableListenerStub extends UploadableListener
{
    public function isUploadedFile()
    {
        return true;
    }

    public function moveUploadedFile($source, $dest)
    {
        return copy($source, $dest);
    }
}
