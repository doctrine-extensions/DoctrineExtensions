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
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $title;

    /**
     * @var int
     */
    private $position;

    /**
     * @var string
     */
    private $grouping;

    /**
     * @var SortableGroup
     */
    private $sortable_group;

    /**
     * @var Collection<int, SortableGroup>
     */
    private $sortable_groups;
}
