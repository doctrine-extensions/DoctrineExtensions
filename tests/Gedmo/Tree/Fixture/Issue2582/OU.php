<?php

namespace Gedmo\Tests\Tree\Fixture\Issue2582;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @Gedmo\Tree(type="nested")
 *
 * @ORM\Table(name="ous")
 * @ORM\Index(name="idx_tree", columns={"lft", "rgt"})
 * @ORM\Entity()
 */
#[ORM\Table(name: 'ous')]
#[ORM\Index(name: 'idx_tree', columns: ['lft', 'rgt'])]
#[ORM\Entity]
#[Gedmo\Tree(type: 'nested')]
class OU
{
    /**
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id()
     */
    #[ORM\Column('id', 'guid')]
    #[ORM\Id]
    private string $id;

    /**
     * @Gedmo\TreeParent()
     *
     * @ORM\ManyToOne(targetEntity="\Gedmo\Tests\Tree\Fixture\Issue2582\OU", inversedBy="children")
     * @ORM\JoinColumn(name="parent", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    #[Gedmo\TreeParent]
    private ?self $parent = null;

    /**
     * @Gedmo\TreeLeft()
     *
     * @ORM\Column(name="lft", type="integer", options={"unsigned"=true})
     */
    #[ORM\Column(name: 'lft', type: 'integer', options: ['unsigned' => true])]
    #[Gedmo\TreeLeft]
    private int $left = 1;

    /**
     * @Gedmo\TreeLevel()
     *
     * @ORM\Column(name="lvl", type="integer", options={"unsigned"=true})
     */
    #[ORM\Column(name: 'lvl', type: 'integer', options: ['unsigned' => true])]
    #[Gedmo\TreeLevel]
    private int $level = 0;

    /**
     * @Gedmo\TreeRight()
     *
     * @ORM\Column(name="rgt", type="integer", options={"unsigned"=true})
     */
    #[ORM\Column(name: 'rgt', type: 'integer', options: ['unsigned' => true])]
    #[Gedmo\TreeRight]
    private int $right = 2;

    /**
     * @ORM\OneToMany(targetEntity="\Gedmo\Tests\Tree\Fixture\Issue2582\OU", mappedBy="parent")
     * @ORM\OrderBy({"left" = "ASC"})
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    #[ORM\OrderBy(['left' => 'ASC'])]
    private Collection $children;

    public function __construct(string $id, ?self $parent = null)
    {
        $this->id       = $id;
        $this->children = new ArrayCollection();
        $this->parent   = $parent;
        if ($parent) {
            $parent->children->add($this);
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getParent(): ?OU
    {
        return $this->parent;
    }

    public function getLeft(): int
    {
        return $this->left;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getRight(): int
    {
        return $this->right;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }
}
