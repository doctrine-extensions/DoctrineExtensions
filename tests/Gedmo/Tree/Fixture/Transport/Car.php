<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Tree\Fixture\Transport;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

/**
 * @Gedmo\Tree(type="nested")
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 */
#[ORM\Entity(repositoryClass: NestedTreeRepository::class)]
#[Gedmo\Tree(type: 'nested')]
class Car extends Vehicle
{
    /**
     * @var Collection<int, self>
     *
     * @ORM\OneToMany(targetEntity="Car", mappedBy="parent")
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    protected $children;

    /**
     * @var self|null
     *
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="Car", inversedBy="children")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Gedmo\TreeParent]
    private $parent;

    /**
     * @var int|null
     *
     * @Gedmo\TreeLeft
     * @ORM\Column(type="integer", nullable=true)
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Gedmo\TreeLeft]
    private $lft;

    /**
     * @var int|null
     *
     * @Gedmo\TreeRight
     * @ORM\Column(type="integer", nullable=true)
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Gedmo\TreeRight]
    private $rgt;

    /**
     * @var int|null
     *
     * @Gedmo\TreeRoot
     * @ORM\Column(type="integer", nullable=true)
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Gedmo\TreeRoot]
    private $root;

    /**
     * @var int|null
     *
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer", nullable=true)
     */
    #[ORM\Column(name: 'lvl', type: Types::INTEGER, nullable: true)]
    #[Gedmo\TreeLevel]
    private $classLevel;

    public function setParent(?self $parent = null): void
    {
        $this->parent = $parent;
    }

    /**
     * @return Collection<int, self>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function getRoot(): ?int
    {
        return $this->root;
    }

    public function getLeft(): ?int
    {
        return $this->lft;
    }

    public function getRight(): ?int
    {
        return $this->rgt;
    }

    public function getClassLevel(): ?int
    {
        return $this->classLevel;
    }
}
