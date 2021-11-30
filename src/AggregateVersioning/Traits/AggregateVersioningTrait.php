<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\AggregateVersioning\Traits;

use Doctrine\DBAL\Types\Types;
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
    #[ORM\Column(type: Types::INTEGER)]
    protected $aggregateVersion;
    /**
     * @var int
     *
     * @ORM\Version
     * @ORM\Column(type="integer")
     */
    #[ORM\Version]
    #[ORM\Column(type: Types::INTEGER)]
    protected $version;

    public function updateAggregateVersion(): void
    {
        ++$this->aggregateVersion;
    }
}
