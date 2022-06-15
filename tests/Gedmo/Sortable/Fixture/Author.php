<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sortable\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sortable\Entity\Repository\SortableRepository;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Sortable\Entity\Repository\SortableRepository")
 */
#[ORM\Entity(repositoryClass: SortableRepository::class)]
class Author
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="name", type="string")
     */
    #[ORM\Column(name: 'name', type: Types::STRING)]
    private $name;

    /**
     * @var Paper|null
     *
     * @Gedmo\SortableGroup
     * @ORM\ManyToOne(targetEntity="Paper", inversedBy="authors")
     */
    #[Gedmo\SortableGroup]
    #[ORM\ManyToOne(targetEntity: Paper::class, inversedBy: 'authors')]
    private $paper;

    /**
     * @var int|null
     *
     * @Gedmo\SortablePosition
     * @ORM\Column(name="position", type="integer")
     */
    #[Gedmo\SortablePosition]
    #[ORM\Column(name: 'position', type: Types::INTEGER)]
    private $position;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getPaper(): ?Paper
    {
        return $this->paper;
    }

    public function setPaper(?Paper $paper): void
    {
        $this->paper = $paper;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): void
    {
        $this->position = $position;
    }
}
