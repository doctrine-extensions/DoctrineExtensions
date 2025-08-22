<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Tree\Fixture\Issue2616;

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
     *
     * @var Category|null
     */
    #[Gedmo\TreeParent]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'category_id', onDelete: 'cascade')]
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    protected $parent;

    /**
     * @ORM\OneToMany(targetEntity="\Gedmo\Tests\Tree\Fixture\Issue2616\Category", mappedBy="parent", fetch="EXTRA_LAZY")
     *
     * @var Category[]|null
     */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class, fetch: 'EXTRA_LAZY')]
    protected $children;

    /**
     * @ORM\OneToOne(targetEntity="\Gedmo\Tests\Tree\Fixture\Issue2616\Page", mappedBy="category", cascade={"remove"})
     *
     * @var Page|null
     */
    #[ORM\OneToOne(targetEntity: Page::class, mappedBy: 'category', cascade: ['remove'])]
    protected $page;
    /**
     * @var int|null
     *
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
    private $id;

    /**
     * @ORM\Column(name="title", type="string", length=64)
     */
    #[ORM\Column(name: 'title', type: Types::STRING, length: 64)]
    private ?string $title = null;

    /**
     * @Gedmo\TreeLevel
     *
     * @ORM\Column(name="level", type="integer", nullable=true)
     *
     * @var int|null
     */
    #[Gedmo\TreeLevel]
    #[ORM\Column(name: 'level', type: Types::INTEGER, nullable: true)]
    private $level;

    /**
     * @Gedmo\TreePath(separator="/", endsWithSeparator=false)
     *
     * @ORM\Column(name="path", type="string", nullable=true)
     *
     * @var string|null
     */
    #[ORM\Column(name: 'path', type: Types::STRING, nullable: true)]
    #[Gedmo\TreePath(separator: '/', endsWithSeparator: false)]
    private $path;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Category|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param Category|null $parent
     */
    public function setParent($parent): void
    {
        $this->parent = $parent;
    }

    /**
     * @return Page|null
     */
    public function getPage()
    {
        return $this->page;
    }

    public function setPage(Page $page): void
    {
        $this->page = $page;
        $page->setCategory($this);
    }

    /**
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @param int $level
     */
    public function setLevel($level): void
    {
        $this->level = $level;
    }

    /**
     * @return string|null
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     *
     * @return void
     */
    public function setPath($path)
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
