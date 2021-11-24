<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sortable\Fixture;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Driver\PDO\Exception as PDODriverException;
use Doctrine\DBAL\Driver\PDOException as LegacyPDOException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

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
            // we imitate a foreign key constraint exception because Doctrine
            // does not support SQLite constraints, which must be tested, too.

            $pdoException = new \PDOException('SQLSTATE[23000]: Integrity constraint violation: 1451 Cannot delete or update a parent row: a foreign key constraint fails', 23000);

            // @todo: This check can be removed when dropping support for doctrine/dbal 2.x.
            if (class_exists(LegacyPDOException::class)) {
                throw new ForeignKeyConstraintViolationException(sprintf('An exception occurred while deleting the customer type with id %s.', $this->getId()), new LegacyPDOException($pdoException));
            }

            throw new ForeignKeyConstraintViolationException(PDODriverException::new($pdoException), null);
        }
    }
}
