<?php

namespace Fixture\Unmapped;

class Address
{
    private $id;
    private $city;
    private $street;

    public function getId()
    {
        return $this->id;
    }

    public function setCity($city)
    {
        $this->city = $city;
        return $this;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function setStreet($street)
    {
        $this->street = $street;
        return $this;
    }

    public function getStreet()
    {
        return $this->street;
    }
}
