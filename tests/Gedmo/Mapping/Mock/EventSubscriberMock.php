<?php

namespace Gedmo\Tests\Mapping\Mock;

use Gedmo\Mapping\MappedEventSubscriber;

class EventSubscriberMock extends MappedEventSubscriber
{
    public function getAdapter($args)
    {
        return $this->getEventAdapter($args);
    }

    public function getSubscribedEvents()
    {
        return [];
    }

    protected function getNamespace()
    {
        return 'something';
    }
}
