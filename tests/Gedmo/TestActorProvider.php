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
    /**
     * @var object|string|null
     */
    private $actor;

    /**
     * @param object|string|null $actor
     */
    public function __construct($actor)
    {
        if (!is_string($actor) && !is_object($actor) && null !== $actor) {
            throw new \TypeError(sprintf('The actor must be a string, an object, or null, "%s" given.', gettype($actor)));
        }

        $this->actor = $actor;
    }

    /**
     * @return object|string|null
     */
    public function getActor()
    {
        return $this->actor;
    }
}
