<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Mock;

use Gedmo\Mapping\Event\AdapterInterface;
use Gedmo\Mapping\MappedEventSubscriber;

class EventSubscriberMock extends MappedEventSubscriber
{
    public function getAdapter($args): AdapterInterface
    {
        return $this->getEventAdapter($args);
    }

    public function getSubscribedEvents(): array
    {
        return [];
    }

    protected function getNamespace(): string
    {
        return 'something';
    }
}
