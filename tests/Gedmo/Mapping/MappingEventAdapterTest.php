<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Gedmo\Mapping\Event\Adapter\ORM as EventAdapterORM;
use Gedmo\Tests\Mapping\Mock\EventSubscriberCustomMock;
use Gedmo\Tests\Mapping\Mock\EventSubscriberMock;
use Gedmo\Tests\Mapping\Mock\Mapping\Event\Adapter\ORM as CustomizedORMAdapter;
use PHPUnit\Framework\TestCase;

final class MappingEventAdapterTest extends TestCase
{
    public function testCustomizedAdapter(): void
    {
        $subscriber = new EventSubscriberCustomMock();
        $args = new PrePersistEventArgs(new \stdClass(), static::createStub(EntityManagerInterface::class));

        $adapter = $subscriber->getAdapter($args);
        static::assertInstanceOf(CustomizedORMAdapter::class, $adapter);
    }

    public function testCorrectAdapter(): void
    {
        $emMock = static::createStub(EntityManagerInterface::class);
        $subscriber = new EventSubscriberMock();
        $args = new PrePersistEventArgs(new \stdClass(), $emMock);

        $adapter = $subscriber->getAdapter($args);
        static::assertInstanceOf(EventAdapterORM::class, $adapter);
        static::assertSame($adapter->getObjectManager(), $emMock);
        static::assertInstanceOf(\stdClass::class, $adapter->getObject());
    }

    public function testAdapterBehavior(): void
    {
        $emMock = static::createStub(EntityManagerInterface::class);
        $entity = new \stdClass();

        $args = new PrePersistEventArgs($entity, $emMock);

        $eventAdapter = new EventAdapterORM();
        $eventAdapter->setEventArgs($args);
        static::assertSame($eventAdapter->getObjectManager(), $emMock);
        static::assertInstanceOf(\stdClass::class, $eventAdapter->getObject());
    }
}
