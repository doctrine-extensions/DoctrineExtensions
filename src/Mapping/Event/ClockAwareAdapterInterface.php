<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Mapping\Event;

use Psr\Clock\ClockInterface;

/**
 * Doctrine event adapter supporting a PSR-20 {@see ClockInterface}.
 */
interface ClockAwareAdapterInterface
{
    public function setClock(ClockInterface $clock): void;
}
