<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\SoftDeleteable\Fixture\Listener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Gedmo\SoftDeleteable\SoftDeleteableListener;

final class WithLifecycleEventArgsFromORMTypeListener implements EventSubscriber
{
    public function preSoftDelete(LifecycleEventArgs $args): void
    {
    }

    public function postSoftDelete(LifecycleEventArgs $args): void
    {
    }

    public function getSubscribedEvents(): array
    {
        return [
            SoftDeleteableListener::PRE_SOFT_DELETE,
            SoftDeleteableListener::POST_SOFT_DELETE,
        ];
    }
}
