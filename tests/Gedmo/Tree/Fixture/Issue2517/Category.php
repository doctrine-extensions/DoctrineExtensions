<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Tree\Fixture\Issue2517;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

/**
 * @Gedmo\Tree(type="nested")
 *
 * @ORM\Table(name="categories")
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 */
#[Gedmo\Tree(type: 'nested')]
#[ORM\Table(name: 'categories')]
#[ORM\Entity(repositoryClass: NestedTreeRepository::class)]
class Category
{
    /**
     * @var int|null
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    /**
     * @ORM\Column(name="title", type="string", length=64)
     */
    #[ORM\Column(name: 'title', type: Types::STRING, length: 64)]
    private ?string $title = null;

    /**
     * @var int|null
     *
     * @Gedmo\TreeLeft
     *
     * @ORM\Column(name="lft", type="integer")
     */
    #[ORM\Column(name: 'lft', type: Types::INTEGER)]
    #[Gedmo\TreeLeft]
    private $lft;

    /**
     * @var int|null
     *
     * @Gedmo\TreeRight
     *
     * @ORM\Column(name="rgt", type="integer")
     */
    #[ORM\Column(name: 'rgt', type: Types::INTEGER)]
    #[Gedmo\TreeRight]
    private $rgt;

    /**
     * @var int|null
     *
     * @Gedmo\TreeLevel
     *
     * @ORM\Column(name="lvl", type="integer")
     */
    #[ORM\Column(name: 'lvl', type: Types::INTEGER)]
    #[Gedmo\TreeLevel]
    private $lvl;

    /**
     * @var self|null
     *
     * @Gedmo\TreeRoot
     *
     * @ORM\ManyToOne(targetEntity="Category")
     * @ORM\JoinColumn(name="tree_root", referencedColumnName="id", onDelete="CASCADE")
     */
    #[Gedmo\TreeRoot]
    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'tree_root', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private $root;

    /**
     * @Gedmo\TreeParent
     *
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    #[Gedmo\TreeParent]
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Category $parent = null;

    /**
     * @var Collection<int, Category>
     *
     * @ORM\OneToMany(targetEntity="Category", mappedBy="parent")
     * @ORM\OrderBy({"lft" = "ASC"})
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    #[ORM\OrderBy(['lft' => 'ASC'])]
    private Collection $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function getId(): ?int
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

    public function getRoot(): ?self
    {
        return $this->root;
    }

    public function setParent(?self $parent): void
    {
        $this->parent = $parent;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }
}
