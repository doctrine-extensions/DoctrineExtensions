<?php

namespace Fixture\Unmapped;

class Employee extends Person
{
    private $salary;
    private $occupation;

    public function setSalary($salary)
    {
        $this->salary = $salary;
        return $this;
    }

    public function getSalary()
    {
        return $this->salary;
    }

    public function setOccupation($occupation)
    {
        $this->occupation = $occupation;
        return $this;
    }

    public function getOccupation()
    {
        return $this->occupation;
    }
}
