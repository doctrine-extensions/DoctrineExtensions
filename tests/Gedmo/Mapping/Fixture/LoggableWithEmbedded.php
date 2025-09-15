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
use Gedmo\Loggable\Entity\LogEntry;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 *
 * @Gedmo\Loggable(logEntryClass="Gedmo\Loggable\Entity\LogEntry")
 */
#[ORM\Entity]
#[Gedmo\Loggable(logEntryClass: LogEntry::class)]
class LoggableWithEmbedded
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * @ORM\Column(name="title", type="string")
     *
     * @Gedmo\Versioned
     */
    #[ORM\Column(name: 'title', type: Types::STRING)]
    #[Gedmo\Versioned]
    private ?string $title = null;

    /**
     * @ORM\Embedded(class="Gedmo\Tests\Mapping\Fixture\Embedded")
     *
     * @Gedmo\Versioned
     */
    #[ORM\Embedded(class: Embedded::class)]
    #[Gedmo\Versioned]
    private ?Embedded $embedded = null;
}
