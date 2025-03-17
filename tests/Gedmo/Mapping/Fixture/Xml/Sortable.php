<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Fixture\Xml;

use Doctrine\Common\Collections\Collection;
use Gedmo\Tests\Mapping\Fixture\SortableGroup;

class Sortable
{
    private ?int $id = null;

    private ?string $title = null;

    private ?int $position = null;

    private ?string $grouping = null;

    private ?SortableGroup $sortable_group = null;

    /**
     * @var Collection<int, SortableGroup>
     */
    private Collection $sortable_groups;
}
