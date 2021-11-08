<?php

namespace Gedmo\Tests\Mapping;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Gedmo\Mapping\Event\Adapter\ORM as EventAdapterORM;
use Gedmo\Tests\Mapping\Mock\EventSubscriberCustomMock;
use Gedmo\Tests\Mapping\Mock\EventSubscriberMock;
use Gedmo\Tests\Mapping\Mock\Mapping\Event\Adapter\ORM as CustomizedORMAdapter;

class MappingEventAdapterTest extends \PHPUnit\Framework\TestCase
{
    public function testCustomizedAdapter()
    {
        $emMock = $this->getMockBuilder('Doctrine\\ORM\\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $subscriber = new EventSubscriberCustomMock();
        $args = new LifecycleEventArgs(new \stdClass(), $emMock);

        $adapter = $subscriber->getAdapter($args);
        static::assertInstanceOf(CustomizedORMAdapter::class, $adapter);
    }

    public function testCorrectAdapter()
    {
        $emMock = $this->getMockBuilder('Doctrine\\ORM\\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $subscriber = new EventSubscriberMock();
        $args = new LifecycleEventArgs(new \stdClass(), $emMock);

        $adapter = $subscriber->getAdapter($args);
        static::assertInstanceOf(EventAdapterORM::class, $adapter);
        static::assertSame($adapter->getObjectManager(), $emMock);
        static::assertInstanceOf(\stdClass::class, $adapter->getObject());
    }

    public function testAdapterBehavior()
    {
        $eventArgsMock = $this->getMockBuilder('Doctrine\\ORM\\Event\\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $eventArgsMock->expects(static::once())
            ->method('getEntityManager');

        $eventArgsMock->expects(static::once())
            ->method('getEntity');

        $eventAdapter = new EventAdapterORM();
        $eventAdapter->setEventArgs($eventArgsMock);
        $eventAdapter->getObjectManager();
        $eventAdapter->getObject();
    }
}
