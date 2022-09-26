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

/**
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator", type="string")
 * @ORM\DiscriminatorMap({"base": "BaseNode", "node": "Node"})
 * @Gedmo\Tree(type="nested")
 */
#[ORM\Entity(repositoryClass: NestedTreeRepository::class)]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discriminator', type: Types::STRING)]
#[ORM\DiscriminatorMap(['base' => BaseNode::class, 'node' => Node::class])]
#[Gedmo\Tree(type: 'nested')]
class BaseNode extends ANode
{
    /**
     * @var Collection<int, BaseNode>
     *
     * @ORM\OneToMany(targetEntity="BaseNode", mappedBy="parent")
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    private $children;

    /**
     * @var \DateTime|null
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private $created;

    /**
     * @var string|null
     *
     * @ORM\Column(length=128, unique=true)
     */
    #[ORM\Column(length: 128, unique: true)]
    private $identifier;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable]
    private $updated;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function getCreated(): ?\DateTime
    {
        return $this->created;
    }

    public function getUpdated(): ?\DateTime
    {
        return $this->updated;
    }

    /**
     * @return Collection<int, BaseNode>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(?string $identifier): void
    {
        $this->identifier = $identifier;
    }
}
