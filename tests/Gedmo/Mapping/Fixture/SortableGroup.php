<?php

namespace Mapping\Fixture;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="test_sortable_groups")
 * @ORM\Entity
 */
class SortableGroup
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(length=64)
     */
    private $name;
}
