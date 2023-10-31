<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\SoftDeleteable\Event\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs as PersistenceLifecycleEventArgs;

if (!class_exists(LifecycleEventArgs::class)) {
    /** @template-extends PersistenceLifecycleEventArgs<EntityManagerInterface> */
    final class PostSoftDeleteEventArgs extends PersistenceLifecycleEventArgs
    {
    }
} else {
    final class PostSoftDeleteEventArgs extends LifecycleEventArgs
    {
    }
}
