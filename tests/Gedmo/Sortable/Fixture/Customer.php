<?php

namespace Sortable\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Sortable\Fixture\CustomerType;

/**
 * @ORM\Entity
 */
class Customer
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(name="name", type="string")
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="CustomerType", inversedBy="customers")
     */
    private $type;

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType(CustomerType $type)
    {
        $this->type = $type;
        if (!$type->getCustomers()->contains($this)) {
            $type->addCustomer($this);
        }
    }
}
