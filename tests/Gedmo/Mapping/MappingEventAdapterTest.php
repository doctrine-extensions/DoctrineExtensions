<?php

namespace Gedmo\Mapping;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Gedmo\Mapping\Event\Adapter\ORM as EventAdapterORM;
use Gedmo\Mapping\Mock\EventSubscriberCustomMock;
use Gedmo\Mapping\Mock\EventSubscriberMock;
use Gedmo\Mapping\Mock\Mapping\Event\Adapter\ORM as CustomizedORMAdapter;

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
        $this->assertInstanceOf(CustomizedORMAdapter::class, $adapter);
    }

    public function testCorrectAdapter()
    {
        $emMock = $this->getMockBuilder('Doctrine\\ORM\\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $subscriber = new EventSubscriberMock();
        $args = new LifecycleEventArgs(new \stdClass(), $emMock);

        $adapter = $subscriber->getAdapter($args);
        $this->assertInstanceOf(EventAdapterORM::class, $adapter);
        $this->assertSame($adapter->getObjectManager(), $emMock);
        $this->assertInstanceOf(\stdClass::class, $adapter->getObject());
    }

    public function testAdapterBehavior()
    {
        $eventArgsMock = $this->getMockBuilder('Doctrine\\ORM\\Event\\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $eventArgsMock->expects($this->once())
            ->method('getEntityManager');

        $eventArgsMock->expects($this->once())
            ->method('getEntity');

        $eventAdapter = new EventAdapterORM();
        $eventAdapter->setEventArgs($eventArgsMock);
        $eventAdapter->getObjectManager();
        $eventAdapter->getObject();
    }
}
