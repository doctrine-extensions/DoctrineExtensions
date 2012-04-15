<?php

namespace Gedmo\Uploadable\Stub;

use Gedmo\Uploadable\UploadableListener;


class UploadableListenerStub extends UploadableListener
{
    public $returnFalseOnMoveUploadedFile = false;

    public function moveUploadedFile($source, $dest)
    {
        return $this->returnFalseOnMoveUploadedFile ? false : copy($source, $dest);
    }
}
