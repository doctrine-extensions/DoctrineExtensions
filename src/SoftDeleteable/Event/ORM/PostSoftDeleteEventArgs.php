<?php

declare(strict_types=1);

namespace Gedmo\SoftDeleteable\Event\ORM;

use Doctrine\ORM\Event\LifecycleEventArgs;

final class PostSoftDeleteEventArgs extends LifecycleEventArgs
{
}
