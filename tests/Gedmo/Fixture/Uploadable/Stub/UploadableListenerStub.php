<?php

namespace Gedmo\Fixture\Uploadable\Stub;

use Gedmo\Uploadable\UploadableListener;


class UploadableListenerStub extends UploadableListener
{
    public $returnFalseOnMoveUploadedFile = false;

    public function doMoveFile($source, $dest, $isUploadedFile = true)
    {
        return $this->returnFalseOnMoveUploadedFile ? false : parent::doMoveFile($source, $dest, false);
    }
}
