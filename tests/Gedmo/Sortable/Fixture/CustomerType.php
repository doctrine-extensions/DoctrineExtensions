<?php

namespace Sortable\Fixture;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\DBAL\Driver\PDOException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Sortable\Entity\Repository\SortableRepository")
 * @ORM\HasLifecycleCallbacks
 */
class CustomerType
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
     * @Gedmo\SortablePosition
     * @ORM\Column(name="position", type="integer")
     */
    private $position;

    /**
     * @ORM\OneToMany(targetEntity="Customer", mappedBy="type")
     */
    private $customers;

    public function __construct()
    {
        $this->customers = new ArrayCollection();
    }

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

    public function getPosition()
    {
        return $this->position;
    }

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function getCustomers()
    {
        return $this->customers;
    }

    public function addCustomer(Customer $customer)
    {
        $this->customers->add($customer);
    }

    public function removeCustomer(Customer $customer)
    {
        $this->customers->removeElement($customer);
    }

    /**
     * @ORM\PostRemove
     */
    public function postRemove()
    {
        if ($this->getCustomers()->count() > 0) {
            // we imitate an foreign key constraint exception, because doctrine
            // does not support sqlite constraints, which must be tested, too.
            $pdoException = new \PDOException('SQLSTATE[23000]: Integrity constraint violation: 1451 Cannot delete or update a parent row: a foreign key constraint fails', "23000");
            throw new ForeignKeyConstraintViolationException(sprintf('An exception occurred while deleting the customer type with id %s.', $this->getId()), new PDOException($pdoException));
        }
    }
}
