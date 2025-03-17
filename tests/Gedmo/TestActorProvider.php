<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests;

use Gedmo\Tool\ActorProviderInterface;

final class TestActorProvider implements ActorProviderInterface
{
    public function __construct(private readonly string|object|null $actor) {}

    public function getActor(): string|object|null
    {
        return $this->actor;
    }
}
