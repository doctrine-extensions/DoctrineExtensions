<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class Embedded
 *
 * @author Fabian Sabau <fabian.sabau@socialbit.de>
 */
#[ORM\Embeddable]
class Embedded
{
    /**
     * @var string
     */
    #[ORM\Column(type: Types::STRING)]
    private ?string $subtitle = null;
}
