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

/**
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\MaterializedPathRepository")
 *
 * @Gedmo\Tree(type="materializedPath")
 */
#[ORM\Entity(repositoryClass: MaterializedPathRepository::class)]
#[Gedmo\Tree(type: 'materializedPath')]
class MPFeaturesCategory
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    /**
     * @Gedmo\TreePath(appendId=false, startsWithSeparator=true, endsWithSeparator=false)
     *
     * @ORM\Column(name="path", type="string", length=3000, nullable=true)
     */
    #[ORM\Column(name: 'path', type: Types::STRING, length: 3000, nullable: true)]
    #[Gedmo\TreePath(appendId: false, startsWithSeparator: true, endsWithSeparator: false)]
    private ?string $path = null;

    /**
     * @var string|null
     *
     * @Gedmo\TreePathHash
     *
     * @ORM\Column(name="pathhash", type="string", length=32, nullable=true)
     */
    #[ORM\Column(name: 'pathhash', type: Types::STRING, length: 32, nullable: true)]
    #[Gedmo\TreePathHash]
    private $pathHash;

    /**
     * @Gedmo\TreePathSource
     *
     * @ORM\Column(name="title", type="string", length=64)
     */
    #[ORM\Column(name: 'title', type: Types::STRING, length: 64)]
    #[Gedmo\TreePathSource]
    private ?string $title = null;

    /**
     * @Gedmo\TreeParent
     *
     * @ORM\ManyToOne(targetEntity="MPFeaturesCategory", inversedBy="children")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Gedmo\TreeParent]
    private ?MPFeaturesCategory $parentId = null;

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
     * @ORM\OneToMany(targetEntity="MPFeaturesCategory", mappedBy="parent")
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
        $this->children = new ArrayCollection();
        $this->comments = new ArrayCollection();
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

    public function setParent(?self $parent = null): void
    {
        $this->parentId = $parent;
    }

    public function getParent(): ?self
    {
        return $this->parentId;
    }

    public function setPath(?string $path): void
    {
        $this->path = $path;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function getTreeRootValue(): ?string
    {
        return $this->treeRootValue;
    }

    public function getPathHash(): ?string
    {
        return $this->pathHash;
    }
}
