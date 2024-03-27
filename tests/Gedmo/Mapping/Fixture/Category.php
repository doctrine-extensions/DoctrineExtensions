<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Fixture;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Loggable\Entity\LogEntry;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sluggable\Handler\RelativeSlugHandler;
use Gedmo\Sluggable\Handler\TreeSlugHandler;
use Gedmo\Tests\Translatable\Fixture\CategoryTranslation;

#[ORM\Entity]
#[ORM\Table(name: 'categories')]
#[Gedmo\Loggable(logEntryClass: LogEntry::class)]
#[Gedmo\TranslationEntity(class: CategoryTranslation::class)]
#[Gedmo\Tree(type: 'nested')]
class Category extends BaseCategory
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 64)]
    #[Gedmo\Translatable]
    private ?string $title = null;

    #[ORM\Column(type: Types::STRING, length: 64)]
    #[Gedmo\Slug(fields: ['title'], style: 'camel', separator: '_')]
    #[Gedmo\SlugHandler(class: RelativeSlugHandler::class, options: ['relationField' => 'parent', 'relationSlugField' => 'slug', 'separator' => '/'])]
    #[Gedmo\SlugHandler(class: TreeSlugHandler::class, options: ['parentRelationField' => 'parent', 'separator' => '/'])]
    private ?string $slug = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    private Collection $children;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[Gedmo\TreeParent]
    private ?Category $parent = null;

    /**
     * @var \DateTimeInterface
     */
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Gedmo\Timestampable(on: 'change', field: 'title', value: 'Test')]
    private ?\DateTimeInterface $changed = null;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    /**
     * @return int $id
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    /**
     * @return string $slug
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    public function addChildren(self $children): void
    {
        $this->children[] = $children;
    }

    /**
     * @return Collection<int, self> $children
     */
    public function getChildren()
    {
        return $this->children;
    }

    public function setParent(self $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * @return self $parent
     */
    public function getParent(): self
    {
        return $this->parent;
    }
}
