<?php

namespace Fixture\Unmapped;

class Nameable
{
    protected $name;

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }
}
