<?php

namespace Gedmo\Uploadable\FileInfo;


abstract class BaseFileInfo
{
    protected $identifier;

    public function __construct($identifier = '_default')
    {
        $this->identifier = $identifier;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

}