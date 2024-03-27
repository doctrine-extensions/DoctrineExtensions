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
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Gedmo\Tree\Node as NodeInterface;

#[ORM\Entity(repositoryClass: NestedTreeRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Gedmo\Tree(type: 'nested')]
class CategoryUuid implements NodeInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\Column(name: 'id', type: Types::STRING, nullable: false)]
    private ?string $id = null;

    #[ORM\Column(name: 'title', type: Types::STRING, length: 64)]
    private ?string $title = null;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'lft', type: Types::INTEGER)]
    #[Gedmo\TreeLeft]
    private ?int $lft = null;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'rgt', type: Types::INTEGER)]
    #[Gedmo\TreeRight]
    private ?int $rgt = null;

    /**
     *          * })
     */
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Gedmo\TreeParent]
    private ?CategoryUuid $parentId = null;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'lvl', type: Types::INTEGER)]
    #[Gedmo\TreeLevel]
    private ?int $level = null;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'root', type: Types::STRING)]
    #[Gedmo\TreeRoot]
    private ?string $root = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    private Collection $children;

    /**
     * @var Collection<int, Article>
     */
    #[ORM\OneToMany(targetEntity: Article::class, mappedBy: 'category')]
    private Collection $comments;

    private ?NodeInterface $sibling = null;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    /**
     * Creates a random uuid on persist
     */
    #[ORM\PrePersist]
    public function createId(): void
    {
        $this->id = bin2hex(pack('N2', random_int(0, mt_getrandmax()), random_int(0, mt_getrandmax())));
    }

    public function getId(): ?string
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

    public function setParent(self $parent): void
    {
        $this->parentId = $parent;
    }

    public function getParent(): ?self
    {
        return $this->parentId;
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

    public function setSibling(NodeInterface $node): void
    {
        $this->sibling = $node;
    }

    public function getSibling(): ?NodeInterface
    {
        return $this->sibling;
    }
}
