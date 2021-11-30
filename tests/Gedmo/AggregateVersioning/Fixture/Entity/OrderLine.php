<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
#[AggregateVersioning(aggregateRootMethod: 'getOrder')]
class OrderLine implements AggregateEntity
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
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

    public function getId(): int
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
