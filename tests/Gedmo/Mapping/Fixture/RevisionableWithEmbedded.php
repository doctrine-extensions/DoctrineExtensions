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
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Revisionable\Entity\Revision;

/**
 * @ORM\Entity
 *
 * @Gedmo\Revisionable(revisionClass="Gedmo\Revisionable\Entity\Revision")
 */
#[ORM\Entity]
#[Gedmo\Revisionable(revisionClass: Revision::class)]
class RevisionableWithEmbedded
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
     * @ORM\Column(name="title", type="string", length=64)
     *
     * @Gedmo\Versioned
     */
    #[ORM\Column(name: 'title', type: Types::STRING, length: 64)]
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
