<?php

namespace Fixture\Timestampable\Superclassed;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\MappedSuperclass
*/
class Trackable
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\Column(length=32)
     */
    protected $name;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="change", field="name")
     */
    protected $nameChangedAt;

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setNameChangedAt($nameChangedAt)
    {
        $this->nameChangedAt = $nameChangedAt;
        return $this;
    }

    public function getNameChangedAt()
    {
        return $this->nameChangedAt;
    }
}
