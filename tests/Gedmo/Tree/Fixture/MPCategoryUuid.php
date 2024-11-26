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
use Gedmo\Tree\Entity\Repository\MaterializedPathRepository;
use Symfony\Component\Uid\UuidV4;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\MaterializedPathRepository")
 *
 * @Gedmo\Tree(type="materializedPath")
 */
#[ORM\Entity(repositoryClass: MaterializedPathRepository::class)]
#[Gedmo\Tree(type: 'materializedPath')]
class MPCategoryUuid
{
    /**
     * @Gedmo\TreePathSource
     *
     * @ORM\Id
     * @ORM\Column(type="uuid")
     */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[Gedmo\TreePathSource]
    private UuidV4 $id;

    /**
     * @Gedmo\TreePath
     *
     * @ORM\Column(name="path", type="string", length=3000, nullable=true)
     */
    #[ORM\Column(name: 'path', type: Types::STRING, length: 3000, nullable: true)]
    #[Gedmo\TreePath]
    private ?string $path = null;

    /**
     * @ORM\Column(name="title", type="string", length=64)
     */
    #[ORM\Column(name: 'title', type: Types::STRING, length: 64)]
    private ?string $title = null;

    /**
     * @Gedmo\TreeParent
     *
     * @ORM\ManyToOne(targetEntity="MPCategoryUuid", inversedBy="children")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Gedmo\TreeParent]
    private ?MPCategoryUuid $parentId = null;

    /**
     * @var int|null
     *
     * @Gedmo\TreeLevel
     *
     * @ORM\Column(name="lvl", type="integer", nullable=true)
     */
    #[ORM\Column(name: 'lvl', type: Types::INTEGER, nullable: true)]
    #[Gedmo\TreeLevel]
    private $level;

    /**
     * @var string|null
     *
     * @Gedmo\TreeRoot
     *
     * @ORM\Column(name="tree_root_value", type="string", nullable=true)
     */
    #[ORM\Column(name: 'tree_root_value', type: Types::STRING, nullable: true)]
    #[Gedmo\TreeRoot]
    private $treeRootValue;

    /**
     * @var Collection<int, self>
     *
     * @ORM\OneToMany(targetEntity="MPCategoryUuid", mappedBy="parent")
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    private Collection $children;

    /**
     * @var Collection<int, Article>
     *
     * @ORM\OneToMany(targetEntity="Article", mappedBy="category")
     */
    #[ORM\OneToMany(targetEntity: Article::class, mappedBy: 'category')]
    private Collection $comments;

    public function __construct()
    {
        $this->id = new UuidV4();
        $this->children = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?UuidV4
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function setParent(?self $parent = null): void
    {
        $this->parentId = $parent;
    }

    public function getParent(): ?self
    {
        return $this->parentId;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): void
    {
        $this->path = $path;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function getTreeRootValue(): ?string
    {
        return $this->treeRootValue;
    }
}
