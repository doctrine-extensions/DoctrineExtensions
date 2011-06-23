<?php

namespace Tree\Fixture\Transport;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Engine
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(length=32)
     */
    private $type;

    /**
     * @ORM\Column(type="integer")
     */
    private $valves;

    public function getId()
    {
        return $this->id;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setValves($valves)
    {
        $this->valves = $valves;
    }

    public function getValves()
    {
        return $this->valves;
    }
}

