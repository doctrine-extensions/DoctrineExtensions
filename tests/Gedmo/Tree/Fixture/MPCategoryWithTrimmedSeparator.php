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
 * @Gedmo\Tree(type="materializedPath")
 */
#[ORM\Entity(repositoryClass: MaterializedPathRepository::class)]
#[Gedmo\Tree(type: 'materializedPath')]
class MPCategoryWithTrimmedSeparator
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
     * @var string|null
     *
     * @Gedmo\TreePath(appendId=false, startsWithSeparator=false, endsWithSeparator=false)
     * @ORM\Column(name="path", type="string", length=3000, nullable=true)
     */
    #[ORM\Column(name: 'path', type: Types::STRING, length: 3000, nullable: true)]
    #[Gedmo\TreePath(appendId: false, startsWithSeparator: false, endsWithSeparator: false)]
    private $path;

    /**
     * @var string|null
     *
     * @Gedmo\TreePathSource
     * @ORM\Column(name="title", type="string", length=64)
     */
    #[ORM\Column(name: 'title', type: Types::STRING, length: 64)]
    #[Gedmo\TreePathSource]
    private $title;

    /**
     * @var self|null
     *
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="MPCategoryWithTrimmedSeparator", inversedBy="children")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Gedmo\TreeParent]
    private $parentId;

    /**
     * @var int|null
     *
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer", nullable=true)
     */
    #[ORM\Column(name: 'lvl', type: Types::INTEGER, nullable: true)]
    #[Gedmo\TreeLevel]
    private $level;

    /**
     * @var Collection<int, self>
     *
     * @ORM\OneToMany(targetEntity="MPCategoryWithTrimmedSeparator", mappedBy="parent")
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    private $children;

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

    public function setParent(self $parent = null): void
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
}
