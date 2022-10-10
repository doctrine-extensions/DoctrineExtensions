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
use Gedmo\Tests\Tree\Fixture\Repository\BehavioralCategoryRepository;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Tests\Tree\Fixture\Repository\BehavioralCategoryRepository")
 * @Gedmo\Tree(type="nested")
 */
#[ORM\Entity(repositoryClass: BehavioralCategoryRepository::class)]
#[Gedmo\Tree(type: 'nested')]
class BehavioralCategory
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
     * @Gedmo\Translatable
     * @ORM\Column(name="title", type="string", length=64)
     */
    #[ORM\Column(name: 'title', type: Types::STRING, length: 64)]
    #[Gedmo\Translatable]
    private $title;

    /**
     * @var int|null
     *
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer", nullable=true)
     */
    #[ORM\Column(name: 'lft', type: Types::INTEGER, nullable: true)]
    #[Gedmo\TreeLeft]
    private $lft;

    /**
     * @var int|null
     *
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer", nullable=true)
     */
    #[ORM\Column(name: 'rgt', type: Types::INTEGER, nullable: true)]
    #[Gedmo\TreeRight]
    private $rgt;

    /**
     * @var self|null
     *
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="BehavioralCategory", inversedBy="children")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Gedmo\TreeParent]
    private $parent;

    /**
     * @var Collection<int, self>
     *
     * @ORM\OneToMany(targetEntity="BehavioralCategory", mappedBy="parent")
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    private $children;

    /**
     * @var string|null
     *
     * @Gedmo\Translatable
     * @Gedmo\Slug(fields={"title"})
     * @ORM\Column(name="slug", type="string", length=128, unique=true)
     */
    #[ORM\Column(name: 'slug', type: Types::STRING, length: 128, unique: true)]
    #[Gedmo\Translatable]
    #[Gedmo\Slug(fields: ['title'])]
    private $slug;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setParent(self $parent): void
    {
        $this->parent = $parent;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }
}
