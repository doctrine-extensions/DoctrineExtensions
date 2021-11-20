<?php

declare(strict_types=1);

namespace Gedmo\AggregateVersioning\Traits;

use Doctrine\ORM\Mapping as ORM;

/**
 * Aggregate Versioning Trait
 *
 * @author Maksim Vorozhtsov <myks1992@mail.ru>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
trait AggregateVersioningTrait
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    protected $aggregateVersion;
    /**
     * @var int
     *
     * @ORM\Version
     * @ORM\Column(type="integer")
     */
    protected $version;

    public function updateAggregateVersion(): void
    {
        ++$this->aggregateVersion;
    }
}
