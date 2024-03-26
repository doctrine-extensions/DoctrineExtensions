<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Tree\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\MappedSuperclass]
class ANode
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * @var int|null
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Gedmo\TreeLeft]
    private ?int $lft = null;

    /**
     * @var int|null
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Gedmo\TreeRight]
    private ?int $rgt = null;

    #[ORM\ManyToOne(targetEntity: BaseNode::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Gedmo\TreeParent]
    private ?BaseNode $parent = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setParent(?BaseNode $parent = null): void
    {
        $this->parent = $parent;
    }

    public function getParent(): ?BaseNode
    {
        return $this->parent;
    }
}
