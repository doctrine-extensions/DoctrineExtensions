<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Tree\Fixture\Issue2616;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @Gedmo\Tree(type="materializedPath")
 *
 * @ORM\Table(name="category")
 * @ORM\Entity
 */
#[ORM\Table(name: 'category')]
#[ORM\Entity]
#[Gedmo\Tree(type: 'materializedPath')]
class Category
{
    /**
     * @ORM\ManyToOne(targetEntity="\Gedmo\Tests\Tree\Fixture\Issue2616\Category", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="category_id", onDelete="cascade")
     *
     * @Gedmo\TreeParent
     */
    #[Gedmo\TreeParent]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'category_id', onDelete: 'cascade')]
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    protected ?Category $parent = null;

    /**
     * @ORM\OneToMany(targetEntity="\Gedmo\Tests\Tree\Fixture\Issue2616\Category", mappedBy="parent", fetch="EXTRA_LAZY")
     *
     * @var Collection<int, Category>
     */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class, fetch: 'EXTRA_LAZY')]
    protected Collection $children;

    /**
     * @ORM\OneToOne(targetEntity="\Gedmo\Tests\Tree\Fixture\Issue2616\Page", mappedBy="category", cascade={"remove"})
     */
    #[ORM\OneToOne(targetEntity: Page::class, mappedBy: 'category', cascade: ['remove'])]
    protected ?Page $page = null;

    /**
     * @ORM\Column(name="category_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     *
     * @Gedmo\TreePathSource
     */
    #[Gedmo\TreePathSource]
    #[ORM\Column(name: 'category_id', type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    #[ORM\Id]
    private ?int $id = null;

    /**
     * @ORM\Column(name="title", type="string", length=64)
     */
    #[ORM\Column(name: 'title', type: Types::STRING, length: 64)]
    private ?string $title = null;

    /**
     * @Gedmo\TreeLevel
     *
     * @ORM\Column(name="level", type="integer", nullable=true)
     */
    #[Gedmo\TreeLevel]
    #[ORM\Column(name: 'level', type: Types::INTEGER, nullable: true)]
    private ?int $level = null;

    /**
     * @Gedmo\TreePath(separator="/", endsWithSeparator=false)
     *
     * @ORM\Column(name="path", type="string", nullable=true)
     */
    #[ORM\Column(name: 'path', type: Types::STRING, nullable: true)]
    #[Gedmo\TreePath(separator: '/', endsWithSeparator: false)]
    private ?string $path = null;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): void
    {
        $this->parent = $parent;
    }

    public function getPage(): ?Page
    {
        return $this->page;
    }

    public function setPage(Page $page): void
    {
        $this->page = $page;
        $page->setCategory($this);
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(?int $level): void
    {
        $this->level = $level;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): void
    {
        $this->path = $path;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }
}
