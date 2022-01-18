<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Gedmo\Mapping\Event\Adapter\ORM as EventAdapterORM;
use Gedmo\Tests\Mapping\Mock\EventSubscriberCustomMock;
use Gedmo\Tests\Mapping\Mock\EventSubscriberMock;
use Gedmo\Tests\Mapping\Mock\Mapping\Event\Adapter\ORM as CustomizedORMAdapter;

final class MappingEventAdapterTest extends \PHPUnit\Framework\TestCase
{
    public function testCustomizedAdapter(): void
    {
        $emMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $subscriber = new EventSubscriberCustomMock();
        $args = new LifecycleEventArgs(new \stdClass(), $emMock);

        $adapter = $subscriber->getAdapter($args);
        static::assertInstanceOf(CustomizedORMAdapter::class, $adapter);
    }

    public function testCorrectAdapter(): void
    {
        $emMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $subscriber = new EventSubscriberMock();
        $args = new LifecycleEventArgs(new \stdClass(), $emMock);

        $adapter = $subscriber->getAdapter($args);
        static::assertInstanceOf(EventAdapterORM::class, $adapter);
        static::assertSame($adapter->getObjectManager(), $emMock);
        static::assertInstanceOf(\stdClass::class, $adapter->getObject());
    }

    public function testAdapterBehavior(): void
    {
        $eventArgsMock = $this->getMockBuilder(LifecycleEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();
        $eventArgsMock->expects(static::once())
            ->method('getEntityManager');

        $eventArgsMock->expects(static::once())
            ->method('getEntity')
            ->willReturn(new \stdClass());

        $eventAdapter = new EventAdapterORM();
        $eventAdapter->setEventArgs($eventArgsMock);
        $eventAdapter->getObjectManager();
        $eventAdapter->getObject();
    }
}
