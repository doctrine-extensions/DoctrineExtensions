<?php

declare(strict_types=1);

namespace Gedmo\SoftDeleteable\Event\ODM;

use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;

final class PreSoftDeleteEventArgs extends LifecycleEventArgs
{
}
