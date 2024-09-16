<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Tree\Fixture;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 *
 * @Gedmo\Tree(type="nested")
 */
#[ORM\Entity(repositoryClass: NestedTreeRepository::class)]
#[Gedmo\Tree(type: 'nested')]
class RootAssociationCategoryBinaryUuid
{
    /**
     * @var Collection<int, self>
     *
     * @ORM\OneToMany(targetEntity="RootAssociationCategory", mappedBy="parent")
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    protected $children;
    /**
     * @ORM\Column(name="id", type="uuid", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\Column(name: 'id', type: UuidType::NAME, nullable: false)]
    private ?Uuid $id = null;

    /**
     * @ORM\Column(name="title", type="string", length=64)
     */
    #[ORM\Column(name: 'title', type: Types::STRING, length: 64)]
    private ?string $title = null;

    /**
     * @var int
     *
     * @Gedmo\TreeLeft
     *
     * @ORM\Column(name="lft", type="integer")
     */
    #[ORM\Column(name: 'lft', type: Types::INTEGER)]
    #[Gedmo\TreeLeft]
    private int $lft;

    /**
     * @var int
     *
     * @Gedmo\TreeRight
     *
     * @ORM\Column(name="rgt", type="integer")
     */
    #[ORM\Column(name: 'rgt', type: Types::INTEGER)]
    #[Gedmo\TreeRight]
    private int $rgt;

    /**
     * @Gedmo\TreeParent
     *
     * @ORM\ManyToOne(targetEntity="RootAssociationCategory", inversedBy="children")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Gedmo\TreeParent]
    private ?RootAssociationCategoryBinaryUuid $parent = null;

    /**
     * @var self|null
     *
     * @Gedmo\TreeRoot
     *
     * @ORM\ManyToOne(targetEntity="RootAssociationCategory")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="tree_root", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'tree_root', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Gedmo\TreeRoot]
    private ?RootAssociationCategoryBinaryUuid $root = null;

    /**
     * @var int
     *
     * @Gedmo\TreeLevel
     *
     * @ORM\Column(name="lvl", type="integer")
     */
    #[ORM\Column(name: 'lvl', type: Types::INTEGER)]
    #[Gedmo\TreeLevel]
    private int $level;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->children = new ArrayCollection();
    }

    public function getId(): ?Uuid
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

    public function setParent(?self $parent = null): void
    {
        $this->parent = $parent;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function getRoot(): ?self
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

    public function getLevel(): ?int
    {
        return $this->level;
    }
}
