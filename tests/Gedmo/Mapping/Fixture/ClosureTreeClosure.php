<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Tests\Mapping\Fixture\Xml\ClosureTree;
use Gedmo\Tree\Entity\MappedSuperclass\AbstractClosure;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     indexes={@ORM\Index(name="closure_tree_depth_idx", columns={"depth"})},
 *     uniqueConstraints={@ORM\UniqueConstraint(name="closure_tree_unique_idx", columns={
 *         "ancestor", "descendant"
 *     })}
 * )
 */
#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'closure_tree_unique_idx', columns: ['ancestor', 'descendant'])]
#[ORM\Index(name: 'closure_tree_depth_idx', columns: ['depth'])]
class ClosureTreeClosure extends AbstractClosure
{
    /**
     * @ORM\ManyToOne(targetEntity="Gedmo\Tests\Mapping\Fixture\Xml\ClosureTree")
     * @ORM\JoinColumn(name="ancestor", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    #[ORM\ManyToOne(targetEntity: ClosureTree::class)]
    #[ORM\JoinColumn(name: 'ancestor', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected $ancestor;

    /**
     * @ORM\ManyToOne(targetEntity="Gedmo\Tests\Mapping\Fixture\Xml\ClosureTree")
     * @ORM\JoinColumn(name="descendant", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    #[ORM\ManyToOne(targetEntity: ClosureTree::class)]
    #[ORM\JoinColumn(name: 'descendant', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected $descendant;
}
