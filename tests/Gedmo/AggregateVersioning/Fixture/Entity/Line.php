<?php

declare(strict_types=1);

namespace Gedmo\Tests\AggregateVersioning\Fixture\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable
 *
 * @author Maksim Vorozhtsov <myks1992@mail.ru>
 */
final class Line
{
    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $name;
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $quantity;
    /**
     * @ORM\Column(type="float")
     */
    private $price;

    public function __construct(string $name, int $quantity, float $price)
    {
        $this->name = $name;
        $this->quantity = $quantity;
        $this->price = $price;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrice(): float
    {
        return $this->price;
    }
}
