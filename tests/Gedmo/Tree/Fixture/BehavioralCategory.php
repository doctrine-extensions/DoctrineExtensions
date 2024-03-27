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

#[ORM\Entity(repositoryClass: BehavioralCategoryRepository::class)]
#[Gedmo\Tree(type: 'nested')]
class BehavioralCategory
{
    /**
     * @var int|null
     *
     *                */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: 'title', type: Types::STRING, length: 64)]
    #[Gedmo\Translatable]
    private ?string $title = null;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'lft', type: Types::INTEGER, nullable: true)]
    #[Gedmo\TreeLeft]
    private ?int $lft = null;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'rgt', type: Types::INTEGER, nullable: true)]
    #[Gedmo\TreeRight]
    private ?int $rgt = null;

    /**
     *          * })
     */
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Gedmo\TreeParent]
    private ?BehavioralCategory $parent = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    private Collection $children;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'slug', type: Types::STRING, length: 128, unique: true)]
    #[Gedmo\Translatable]
    #[Gedmo\Slug(fields: ['title'])]
    private ?string $slug = null;

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
