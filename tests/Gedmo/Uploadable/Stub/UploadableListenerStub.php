<?php

namespace Gedmo\Uploadable\Stub;

use Gedmo\Uploadable\UploadableListener;


class UploadableListenerStub extends UploadableListener
{
    public function moveUploadedFile($source, $dest)
    {
        return copy($source, $dest);
    }
}
