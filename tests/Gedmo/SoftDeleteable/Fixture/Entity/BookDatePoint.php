<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\SoftDeleteable\Fixture\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Clock\DatePoint;

/**
 * @ORM\Entity
 *
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 */
#[ORM\Entity]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt')]
class BookDatePoint
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    /**
     * @ORM\Column(name="title", type="string")
     */
    #[ORM\Column(name: 'title', type: Types::STRING)]
    private ?string $title = null;

    /**
     * @ORM\Column(name="deletedAt", type="date_point", nullable=true)
     */
    #[ORM\Column(name: 'deletedAt', type: 'date_point', nullable: true)]
    private ?DatePoint $deletedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setDeletedAt(?DatePoint $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
    }

    public function getDeletedAt(): ?DatePoint
    {
        return $this->deletedAt;
    }
}
