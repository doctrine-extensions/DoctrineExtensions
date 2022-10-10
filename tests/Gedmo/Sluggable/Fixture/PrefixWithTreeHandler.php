<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sluggable\Handler\TreeSlugHandler;
use Gedmo\Sluggable\Sluggable;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 * @Gedmo\Tree(type="nested")
 *
 * @author Dirk Luijk <dirk@luijkwebcreations.nl>
 */
#[ORM\Entity(repositoryClass: NestedTreeRepository::class)]
#[Gedmo\Tree(type: 'nested')]
class PrefixWithTreeHandler implements Sluggable
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
     * @ORM\Column(name="title", type="string", length=64)
     */
    #[ORM\Column(name: 'title', type: Types::STRING, length: 64)]
    private $title;

    /**
     * @var string|null
     *
     * @Gedmo\Slug(handlers={
     *     @Gedmo\SlugHandler(class="Gedmo\Sluggable\Handler\TreeSlugHandler", options={
     *         @Gedmo\SlugHandlerOption(name="parentRelationField", value="parent"),
     *         @Gedmo\SlugHandlerOption(name="separator", value="/")
     *     })
     * }, separator="-", updatable=true, fields={"title"}, prefix="test.")
     * @ORM\Column(name="slug", type="string", length=64, unique=true)
     */
    #[Gedmo\Slug(separator: '-', updatable: true, fields: ['title'], prefix: 'test.')]
    #[Gedmo\SlugHandler(class: TreeSlugHandler::class, options: ['parentRelationField' => 'parent', 'separator' => '/'])]
    #[ORM\Column(name: 'slug', type: Types::STRING, length: 64, unique: true)]
    private $slug;

    /**
     * @var PrefixWithTreeHandler|null
     *
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="PrefixWithTreeHandler")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Gedmo\TreeParent]
    private $parent;

    /**
     * @var int|null
     *
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer")
     */
    #[ORM\Column(name: 'lft', type: Types::INTEGER)]
    #[Gedmo\TreeLeft]
    private $lft;

    /**
     * @var int|null
     *
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer")
     */
    #[ORM\Column(name: 'lvl', type: Types::INTEGER)]
    #[Gedmo\TreeLevel]
    private $lvl;

    /**
     * @var int|null
     *
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer")
     */
    #[ORM\Column(name: 'rgt', type: Types::INTEGER)]
    #[Gedmo\TreeRight]
    private $rgt;

    /**
     * @var int|null
     *
     * @Gedmo\TreeRoot
     * @ORM\Column(name="root", type="integer", nullable=true)
     */
    #[ORM\Column(name: 'root', type: Types::INTEGER, nullable: true)]
    #[Gedmo\TreeRoot]
    private $root;

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

    public function setSlug(?string $slug): void
    {
        $this->slug = $slug;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setParent(self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setLft(?int $lft): self
    {
        $this->lft = $lft;

        return $this;
    }

    public function getLft(): ?int
    {
        return $this->lft;
    }

    public function setLvl(?int $lvl): self
    {
        $this->lvl = $lvl;

        return $this;
    }

    public function getLvl(): ?int
    {
        return $this->lvl;
    }

    public function setRgt(?int $rgt): self
    {
        $this->rgt = $rgt;

        return $this;
    }

    public function getRgt(): ?int
    {
        return $this->rgt;
    }

    public function setRoot(?int $root): self
    {
        $this->root = $root;

        return $this;
    }

    public function getRoot(): ?int
    {
        return $this->root;
    }
}
