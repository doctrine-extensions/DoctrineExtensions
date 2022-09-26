<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Tree\Fixture\Closure;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Tree\Entity\MappedSuperclass\AbstractClosure;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     indexes={@ORM\Index(name="closure_category_without_level_depth_idx", columns={"depth"})},
 *     uniqueConstraints={@ORM\UniqueConstraint(name="closure_category_without_level_unique_idx", columns={
 *         "ancestor", "descendant"
 *     })}
 * )
 */
#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'closure_category_without_level_unique_idx', columns: ['ancestor', 'descendant'])]
#[ORM\Index(name: 'closure_category_without_level_depth_idx', columns: ['depth'])]
class CategoryWithoutLevelClosure extends AbstractClosure
{
    /**
     * @ORM\ManyToOne(targetEntity="Gedmo\Tests\Tree\Fixture\Closure\CategoryWithoutLevel")
     * @ORM\JoinColumn(name="ancestor", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    #[ORM\ManyToOne(targetEntity: CategoryWithoutLevel::class)]
    #[ORM\JoinColumn(name: 'ancestor', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected $ancestor;

    /**
     * @ORM\ManyToOne(targetEntity="Gedmo\Tests\Tree\Fixture\Closure\CategoryWithoutLevel")
     * @ORM\JoinColumn(name="descendant", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    #[ORM\ManyToOne(targetEntity: CategoryWithoutLevel::class)]
    #[ORM\JoinColumn(name: 'descendant', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected $descendant;
}
