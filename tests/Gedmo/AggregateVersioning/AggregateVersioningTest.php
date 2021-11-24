<?php

declare(strict_types=1);

namespace Gedmo\AggregateVersioning;

use Doctrine\Common\EventManager;
use Gedmo\Tests\AggregateVersioning\Fixture\Entity\Line;
use Gedmo\Tests\AggregateVersioning\Fixture\Entity\Order;
use Gedmo\Tests\AggregateVersioning\Fixture\Entity\OrderLine;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for Aggregate version behavior
 *
 * @author Maksim Vorozhtsov <myks1992@mail.ru>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class AggregateVersioningTest extends BaseTestCaseORM
{
    public const ORDER = Order::class;
    public const ORDER_LINE = OrderLine::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $listener = new AggregateVersionListener();
        $evm->addEventSubscriber($listener);

        $this->getMockSqliteEntityManager($evm);
    }

    public function testUpdateAggregate(): void
    {
        $order = $this->getOrder();

        static::assertSame($order->getAggregateVersion(), 1);
        static::assertSame($order->getVersion(), 1);

        $order->close();
        $this->em->flush();

        static::assertSame($order->getAggregateVersion(), 2);
        static::assertSame($order->getVersion(), 2);
    }

    public function testAddAggregateEntity(): void
    {
        $order = $this->getOrder();

        static::assertSame($order->getAggregateVersion(), 1);
        static::assertSame($order->getVersion(), 1);

        $order->addLine(new Line('Name', 12, 55.1));
        $this->em->flush();

        static::assertSame($order->getAggregateVersion(), 2);
        static::assertSame($order->getVersion(), 2);
    }

    public function testEditAggregateEntity(): void
    {
        $order = $this->getOrder();

        $order->addLine($line = new Line('Name', 12, 55.1));
        $this->em->flush();

        static::assertCount(1, $items = $order->getItems());
        static::assertInstanceOf(OrderLine::class, $item = end($items));

        static::assertSame($item->getLine(), $line);

        static::assertSame($order->getAggregateVersion(), 2);
        static::assertSame($order->getVersion(), 2);

        $order->editLine($item->getId(), new Line('New name', 10, 100.2));
        $this->em->flush();

        static::assertSame($order->getAggregateVersion(), 3);
        static::assertSame($order->getVersion(), 3);
    }

    public function testRemoveAggregateEntity(): void
    {
        $order = $this->getOrder();

        $order->addLine($line = new Line('Name', 12, 55.1));
        $this->em->flush();

        static::assertCount(1, $items = $order->getItems());
        static::assertInstanceOf(OrderLine::class, $item = end($items));

        static::assertSame($item->getLine(), $line);

        static::assertSame($order->getAggregateVersion(), 2);
        static::assertSame($order->getVersion(), 2);

        $order->removeLine($item->getId());
        $this->em->flush();

        static::assertCount(0, $items = $order->getItems());
        static::assertSame($order->getAggregateVersion(), 3);
        static::assertSame($order->getVersion(), 3);
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
           self::ORDER,
           self::ORDER_LINE,
       ];
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function getOrder(): Order
    {
        $id = 1;

        if (null === $order = $this->em->getRepository(self::ORDER)->find($id)) {
            $order = new Order($id);
            $this->em->persist($order);
            $this->em->flush();
        }

        return $order;
    }
}
