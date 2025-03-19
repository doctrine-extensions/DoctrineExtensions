<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Entity\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @Gedmo\Tree(type="nested")
 *
 * @ORM\Table(name="ext_categories")
 * @ORM\Entity(repositoryClass="App\Entity\Repository\CategoryRepository")
 *
 * @Gedmo\TranslationEntity(class="App\Entity\CategoryTranslation")
 */
#[Gedmo\Tree(type: 'nested')]
#[ORM\Table(name: 'ext_categories')]
#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[Gedmo\TranslationEntity(class: CategoryTranslation::class)]
class Category implements \Stringable
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    /**
     * @Gedmo\Translatable
     *
     * @ORM\Column(length=64)
     */
    #[Gedmo\Translatable]
    #[ORM\Column(length: 64)]
    private ?string $title = null;

    /**
     * @Gedmo\Translatable
     *
     * @ORM\Column(type="text", nullable=true)
     */
    #[Gedmo\Translatable]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * @Gedmo\Translatable
     * @Gedmo\Slug(fields={"created", "title"})
     *
     * @ORM\Column(length=64, unique=true)
     */
    #[Gedmo\Translatable]
    #[Gedmo\Slug(fields: ['created', 'title'])]
    #[ORM\Column(length: 64, unique: true)]
    private ?string $slug = null;

    /**
     * @Gedmo\TreeLeft
     *
     * @ORM\Column(type="integer")
     */
    #[Gedmo\TreeLeft]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $lft = null;

    /**
     * @Gedmo\TreeRight
     *
     * @ORM\Column(type="integer")
     */
    #[Gedmo\TreeRight]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $rgt = null;

    /**
     * @Gedmo\TreeParent
     *
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    #[Gedmo\TreeParent]
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?self $parent = null;

    /**
     * @Gedmo\TreeRoot
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    #[Gedmo\TreeRoot]
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $root = null;

    /**
     * @Gedmo\TreeLevel
     *
     * @ORM\Column(name="lvl", type="integer")
     */
    #[Gedmo\TreeLevel]
    #[ORM\Column(name: 'lvl', type: Types::INTEGER)]
    private ?int $level = null;

    /**
     * @var Collection<array-key, self>
     *
     * @ORM\OneToMany(targetEntity="Category", mappedBy="parent")
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    private Collection $children;

    /**
     * @Gedmo\Timestampable(on="create")
     *
     * @ORM\Column(type="datetime")
     */
    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $created = null;

    /**
     * @Gedmo\Timestampable(on="update")
     *
     * @ORM\Column(type="datetime")
     */
    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $updated = null;

    /**
     * @Gedmo\Blameable(on="create")
     *
     * @ORM\Column(type="string")
     */
    #[Gedmo\Blameable(on: 'create')]
    #[ORM\Column(type: Types::STRING)]
    private ?string $createdBy = null;

    /**
     * @Gedmo\Blameable(on="update")
     *
     * @ORM\Column(type="string")
     */
    #[Gedmo\Blameable(on: 'update')]
    #[ORM\Column(type: Types::STRING)]
    private ?string $updatedBy = null;

    /**
     * @var Collection<array-key, CategoryTranslation>
     *
     * @ORM\OneToMany(
     *     targetEntity="CategoryTranslation",
     *     mappedBy="object",
     *     cascade={"persist", "remove"}
     * )
     */
    #[ORM\OneToMany(targetEntity: CategoryTranslation::class, mappedBy: 'object', cascade: ['persist', 'remove'])]
    private Collection $translations;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->translations = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->getTitle();
    }

    /**
     * @return Collection<CategoryTranslation>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(CategoryTranslation $t): void
    {
        if (!$this->translations->contains($t)) {
            $this->translations[] = $t;
            $t->setObject($this);
        }
    }

    public function getSlug(): ?string
    {
        return $this->slug;
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

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setParent(?self $parent): void
    {
        $this->parent = $parent;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function getRoot(): ?int
    {
        return $this->root;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    /**
     * @return Collection<self>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function getLeft(): ?int
    {
        return $this->lft;
    }

    public function getRight(): ?int
    {
        return $this->rgt;
    }

    public function getCreated(): ?\DateTime
    {
        return $this->created;
    }

    public function getUpdated(): ?\DateTime
    {
        return $this->updated;
    }

    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
    }
}
