<?php

namespace Fixture\Sluggable\Issue116;

class Country
{
    private $id;
    private $languageCode;
    private $originalName;
    private $alias;

    public function getId()
    {
        return $this->id;
    }

    public function setOriginalName($originalName)
    {
        $this->originalName = $originalName;
    }

    public function getOriginalName()
    {
        return $this->originalName;
    }

    public function getAlias()
    {
        return $this->alias;
    }
}
