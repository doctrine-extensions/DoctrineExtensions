<?php

declare(strict_types=1);

namespace Gedmo\AggregateVersioning;

/**
 * Entity which in needs to be identified as Aggregate Root
 * with updates Aggregate Version
 *
 * @author Maksim Vorozhtsov <myks1992@mail.ru>
 */
interface AggregateRoot
{
    public function updateAggregateVersion(): void;
}
