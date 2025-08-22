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
use Doctrine\Persistence\ObjectManager;
use Gedmo\SoftDeleteable\Event\PostSoftDeleteEventArgs;
use Gedmo\SoftDeleteable\Event\PreSoftDeleteEventArgs;
use Gedmo\SoftDeleteable\SoftDeleteableListener;

final class WithPreAndPostSoftDeleteEventArgsTypeListener implements EventSubscriber
{
    /** @param PreSoftDeleteEventArgs<ObjectManager> $args */
    public function preSoftDelete(PreSoftDeleteEventArgs $args): void
    {
    }

    /** @param PostSoftDeleteEventArgs<ObjectManager> $args */
    public function postSoftDelete(PostSoftDeleteEventArgs $args): void
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
