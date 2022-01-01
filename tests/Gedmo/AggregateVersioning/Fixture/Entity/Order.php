<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\AggregateVersioning\Fixture\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use DomainException;
use Gedmo\AggregateVersioning\AggregateRoot;
use Gedmo\AggregateVersioning\Traits\AggregateVersioningTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="orders")
 *
 * @author Maksim Vorozhtsov <myks1992@mail.ru>
 */
#[ORM\Entity]
#[ORM\Table(name: 'orders')]
class Order implements AggregateRoot
{
    use AggregateVersioningTrait;

    private const STATUS_NEW = 'new';
    private const STATUS_CLOSED = 'closed';
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    private $id;
    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    #[ORM\Column(type: Types::STRING)]
    private $status;
    /**
     * @var OrderLine[]|Collection
     * @ORM\OneToMany(targetEntity="OrderLine", mappedBy="order", orphanRemoval=true, cascade={"persist"})
     */
    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderLine::class, cascade: ['persist'], orphanRemoval: true)]
    private $items;

    public function __construct(int $id)
    {
        $this->id = $id;
        $this->status = self::STATUS_NEW;
        $this->items = new ArrayCollection();
    }

    public function close(): void
    {
        $this->status = self::STATUS_CLOSED;
    }

    public function addLine(Line $line): void
    {
        $this->items->add(new OrderLine($this, $line));
    }

    public function editLine(int $id, Line $line): void
    {
        foreach ($this->items as $item) {
            if ($item->getId() === $id) {
                $item->edit($line);

                return;
            }
        }

        throw new DomainException('Order line not found.');
    }

    public function removeLine(int $id): void
    {
        foreach ($this->items as $item) {
            if ($item->getId() === $id) {
                $this->items->removeElement($item);

                return;
            }
        }

        throw new DomainException('Order line not found.');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getAggregateVersion(): int
    {
        return $this->aggregateVersion;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @return OrderLine[]
     */
    public function getItems(): array
    {
        return $this->items->toArray();
    }
}
