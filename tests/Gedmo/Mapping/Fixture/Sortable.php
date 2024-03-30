<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Fixture;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
#[ORM\Table(name: 'sortables')]
class Sortable
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * @var string
     */
    #[ORM\Column(type: Types::STRING, length: 128)]
    private ?string $title = null;

    /**
     * @var int
     */
    #[ORM\Column(type: Types::INTEGER)]
    #[Gedmo\SortablePosition]
    private ?int $position = null;

    /**
     * @var string
     */
    #[ORM\Column(type: Types::STRING, length: 128)]
    #[Gedmo\SortableGroup]
    private ?string $grouping = null;

    #[ORM\ManyToOne(targetEntity: SortableGroup::class)]
    #[Gedmo\SortableGroup]
    private ?SortableGroup $sortable_group = null;

    /**
     * @var Collection<int, SortableGroup>
     */
    #[ORM\ManyToMany(targetEntity: SortableGroup::class)]
    #[ORM\JoinTable(name: 'sortable_sortable_groups')]
    #[ORM\JoinColumn(name: 'sortable_id')]
    #[ORM\InverseJoinColumn(name: 'group_id')]
    #[Gedmo\SortableGroup]
    private Collection $sortable_groups;

    public function __construct()
    {
        $this->sortable_groups = new ArrayCollection();
    }
}
