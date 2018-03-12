<?php

namespace LaravelDoctrine\Extensions\Uploadable;

use Gedmo\Uploadable\UploadableListener;
use Illuminate\Support\Facades\Facade;

class UploadableFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return UploadableListener::class;
    }
}
