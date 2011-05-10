<?php

namespace Tree\Fixture\Transport;

/**
 * @Entity
 */
class Engine
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    private $id;

    /**
     * @Column(length=32)
     */
    private $type;

    /**
     * @Column(type="integer")
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

