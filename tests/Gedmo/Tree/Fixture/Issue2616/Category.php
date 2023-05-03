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
 * @ORM\Table(name="category")
 * @ORM\Entity
 */
#[
    ORM\Table(name: 'category'),
    ORM\Entity,
    Gedmo\Tree(type: 'materializedPath')
]
class Category
{
    /**
     * @var int|null
     *
     * @ORM\Column(name="category_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     * @Gedmo\TreePathSource
     */
    #[
        ORM\Id,
        ORM\GeneratedValue,
        ORM\Column(name: 'category_id', type: Types::INTEGER),
        Gedmo\TreePathSource
    ]
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="title", type="string", length=64)
     */
    #[ORM\Column(name: 'title', type: Types::STRING, length: 64)]
    private $title;

    /**
     * @ORM\ManyToOne(targetEntity="\Gedmo\Tests\Tree\Fixture\Issue2616\Category", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="category_id", onDelete="cascade")
     * @Gedmo\TreeParent
     */
    #[
        ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children'),
        ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'category_id', onDelete: 'cascade'),
        Gedmo\TreeParent
    ]
    protected $parent;

    /**
     * @ORM\OneToMany(targetEntity="\Gedmo\Tests\Tree\Fixture\Issue2616\Category", mappedBy="parent", fetch="EXTRA_LAZY")
     */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class, fetch: 'EXTRA_LAZY')]
    protected $children;

    /**
     * @ORM\OneToOne(targetEntity="\Gedmo\Tests\Tree\Fixture\Issue2616\Page", mappedBy="category", cascade={"remove"})
     */
    #[ORM\OneToOne(targetEntity: Page::class, mappedBy: 'category', cascade: ['remove'])]
    protected $page;

    /**
     * @Gedmo\TreeLevel
     * @ORM\Column(name="level", type="integer", nullable=true)
     */
    #[
        Gedmo\TreeLevel,
        ORM\Column(name: 'level', type: Types::INTEGER, nullable: true)
    ]
    private $level;

    /**
     * @Gedmo\TreePath(separator="/", endsWithSeparator=false)
     * @ORM\Column(name="path", type="string", nullable=true)
     */
    #[
        ORM\Column(name: 'path', type: Types::STRING, nullable: true),
        Gedmo\TreePath(separator: '/', endsWithSeparator: false)
    ]
    private $path;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent($parent): void
    {
        $this->parent = $parent;
    }

    public function getPage()
    {
        return $this->page;
    }

    public function setPage(Page $page): void
    {
        $this->page = $page;
        $page->setCategory($this);
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function setLevel($level): void
    {
        $this->level = $level;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setPath($path): void
    {
        $this->path = $path;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     */
    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }
}
