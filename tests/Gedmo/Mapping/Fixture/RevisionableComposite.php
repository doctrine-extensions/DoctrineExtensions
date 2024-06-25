<?php

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
class RevisionableComposite
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $one = null;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $two = null;

    /**
     * @ORM\Column(name="title", type="string", length=64)
     *
     * @Gedmo\Versioned
     */
    #[ORM\Column(name: 'title', type: Types::STRING, length: 64)]
    #[Gedmo\Versioned]
    private ?string $title = null;

    public function getOne(): ?int
    {
        return $this->one;
    }

    public function getTwo(): ?int
    {
        return $this->two;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }
}
