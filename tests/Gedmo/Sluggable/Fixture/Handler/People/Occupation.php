<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Fixture\Handler\People;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sluggable\Handler\InversedRelativeSlugHandler;
use Gedmo\Sluggable\Handler\TreeSlugHandler;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

/**
 * @Gedmo\Tree(type="nested")
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 */
#[ORM\Entity(repositoryClass: NestedTreeRepository::class)]
#[Gedmo\Tree(type: 'nested')]
class Occupation
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
     * @ORM\Column(length=64)
     */
    #[ORM\Column(length: 64)]
    private $title;

    /**
     * @var string|null
     *
     * @Gedmo\Slug(handlers={
     *     @Gedmo\SlugHandler(class="Gedmo\Sluggable\Handler\TreeSlugHandler", options={
     *         @Gedmo\SlugHandlerOption(name="parentRelationField", value="parent"),
     *         @Gedmo\SlugHandlerOption(name="separator", value="/")
     *     }),
     *     @Gedmo\SlugHandler(class="Gedmo\Sluggable\Handler\InversedRelativeSlugHandler", options={
     *         @Gedmo\SlugHandlerOption(name="relationClass", value="Gedmo\Tests\Sluggable\Fixture\Handler\People\Person"),
     *         @Gedmo\SlugHandlerOption(name="mappedBy", value="occupation"),
     *         @Gedmo\SlugHandlerOption(name="inverseSlugField", value="slug")
     *     })
     * }, fields={"title"})
     * @ORM\Column(length=64, unique=true)
     */
    #[Gedmo\Slug(fields: ['title'])]
    #[Gedmo\SlugHandler(class: TreeSlugHandler::class, options: ['parentRelationField' => 'parent', 'separator' => '/'])]
    #[Gedmo\SlugHandler(class: InversedRelativeSlugHandler::class, options: ['relationClass' => Person::class, 'mappedBy' => 'occupation', 'inverseSlugField' => 'slug'])]
    #[ORM\Column(length: 64, unique: true)]
    private $slug;

    /**
     * @var Occupation|null
     *
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="Occupation")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Gedmo\TreeParent]
    private $parent;

    /**
     * @var Collection<int, self>
     */
    private $children;

    /**
     * @var int|null
     *
     * @Gedmo\TreeLeft
     * @ORM\Column(type="integer")
     */
    #[ORM\Column(type: Types::INTEGER)]
    #[Gedmo\TreeLeft]
    private $lft;

    /**
     * @var int|null
     *
     * @Gedmo\TreeRight
     * @ORM\Column(type="integer")
     */
    #[ORM\Column(type: Types::INTEGER)]
    #[Gedmo\TreeRight]
    private $rgt;

    /**
     * @var int|null
     *
     * @Gedmo\TreeRoot
     * @ORM\Column(type="integer")
     */
    #[ORM\Column(type: Types::INTEGER)]
    #[Gedmo\TreeRoot]
    private $root;

    /**
     * @var int|null
     *
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer")
     */
    #[ORM\Column(name: 'lvl', type: Types::INTEGER)]
    #[Gedmo\TreeLevel]
    private $level;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function setParent(self $parent = null): void
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

    public function getLevel(): ?int
    {
        return $this->level;
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }
}
