<?php

declare(strict_types=1);

namespace Gedmo\Tests\AggregateVersioning\Fixture\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\AggregateVersioning\AggregateEntity;
use Gedmo\Mapping\Annotation\AggregateVersioning;

/**
 * @ORM\Entity
 * @ORM\Table(name="order_lines")
 * @AggregateVersioning(aggregateRootMethod="getOrder")
 *
 * @author Maksim Vorozhtsov <myks1992@mail.ru>
 */
class OrderLine implements AggregateEntity
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;
    /**
     * @var Order
     *
     * @ORM\ManyToOne(targetEntity="Order", inversedBy="items")
     * @ORM\JoinColumn(name="order_id")
     */
    private $order;
    /**
     * @var Line
     *
     * @ORM\Embedded(class="Line", columnPrefix=false)
     */
    private $line;

    public function __construct(Order $order, Line $line)
    {
        $this->order = $order;
        $this->line = $line;
    }

    public function edit(Line $line): void
    {
        $this->line = $line;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getLine(): Line
    {
        return $this->line;
    }
}
