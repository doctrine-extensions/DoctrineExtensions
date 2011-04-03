<?php

namespace Gedmo\Mapping\Mock;

use Gedmo\Mapping\MappedEventSubscriber;

class EventSubscriberMock extends MappedEventSubscriber
{
    protected function getNamespace()
    {
        return 'something';
    }

    public function getAdapter($args)
    {
        return $this->getEventAdapter($args);
    }

    public function getSubscribedEvents()
    {
        return array();
    }
}