<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Fixture\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @author Fabian Sabau <fabian.sabau@socialbit.de>
 *
 * @ORM\Embeddable
 */
#[ORM\Embeddable]
class EmbeddedRevisionable
{
    /**
     * @ORM\Column(type="string")
     *
     * @Gedmo\Versioned
     */
    #[ORM\Column(type: Types::STRING)]
    #[Gedmo\Versioned]
    private ?string $subtitle = null;
}
