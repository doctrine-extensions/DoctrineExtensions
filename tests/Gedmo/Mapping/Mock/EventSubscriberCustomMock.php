<?php

namespace Gedmo\Tests\Mapping\Mock;

use Gedmo\Mapping\MappedEventSubscriber;

class EventSubscriberCustomMock extends MappedEventSubscriber
{
    protected function getNamespace()
    {
        return __NAMESPACE__;
    }

    public function getAdapter($args)
    {
        return $this->getEventAdapter($args);
    }

    public function getSubscribedEvents()
    {
        return [];
    }
}
