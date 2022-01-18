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
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Driver\PDO\Exception as PDODriverException;
use Doctrine\DBAL\Driver\PDOException as LegacyPDOException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sortable\Entity\Repository\SortableRepository;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Sortable\Entity\Repository\SortableRepository")
 * @ORM\HasLifecycleCallbacks
 */
#[ORM\Entity(repositoryClass: SortableRepository::class)]
#[ORM\HasLifecycleCallbacks]
class CustomerType
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="name", type="string")
     */
    #[ORM\Column(name: 'name', type: Types::STRING)]
    private $name;

    /**
     * @var int|null
     *
     * @Gedmo\SortablePosition
     * @ORM\Column(name="position", type="integer")
     */
    #[Gedmo\SortablePosition]
    #[ORM\Column(name: 'position', type: Types::INTEGER)]
    private $position;

    /**
     * @var Collection<int, Customer>
     *
     * @ORM\OneToMany(targetEntity="Customer", mappedBy="type")
     */
    #[ORM\OneToMany(mappedBy: 'type', targetEntity: Customer::class)]
    private $customers;

    public function __construct()
    {
        $this->customers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): void
    {
        $this->position = $position;
    }

    /**
     * @return Collection<int, Customer>
     */
    public function getCustomers(): Collection
    {
        return $this->customers;
    }

    public function addCustomer(Customer $customer): void
    {
        $this->customers->add($customer);
    }

    public function removeCustomer(Customer $customer): void
    {
        $this->customers->removeElement($customer);
    }

    /**
     * @ORM\PostRemove
     */
    #[ORM\PostRemove]
    public function postRemove(): void
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
