<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sortable\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class Customer
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
     * @var CustomerType|null
     *
     * @ORM\ManyToOne(targetEntity="CustomerType", inversedBy="customers")
     */
    #[ORM\ManyToOne(targetEntity: CustomerType::class, inversedBy: 'customers')]
    private $type;

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

    public function getType(): ?CustomerType
    {
        return $this->type;
    }

    public function setType(CustomerType $type): void
    {
        $this->type = $type;
        if (!$type->getCustomers()->contains($this)) {
            $type->addCustomer($this);
        }
    }
}
