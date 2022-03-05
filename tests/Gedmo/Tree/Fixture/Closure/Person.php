<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Tree\Fixture\Closure;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tree\Entity\Repository\ClosureTreeRepository;

/**
 * @Gedmo\Tree(type="closure")
 * @Gedmo\TreeClosure(class="Gedmo\Tests\Tree\Fixture\Closure\PersonClosure")
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\ClosureTreeRepository")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discriminator", type="string")
 * @ORM\DiscriminatorMap({
 *   "user" = "User"
 *   })
 */
#[Gedmo\Tree(type: 'closure')]
#[Gedmo\TreeClosure(class: PersonClosure::class)]
#[ORM\Entity(repositoryClass: ClosureTreeRepository::class)]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'discriminator', type: Types::STRING)]
#[ORM\DiscriminatorMap(['user' => User::class])]
abstract class Person
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
     * @ORM\Column(name="full_name", type="string", length=64)
     */
    #[ORM\Column(name: 'full_name', type: Types::STRING, length: 64)]
    private $fullName;

    /**
     * @var self|null
     *
     * @Gedmo\TreeParent
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\ManyToOne(targetEntity="Person", inversedBy="children", cascade={"persist"})
     */
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Gedmo\TreeParent]
    private $parent;

    /**
     * @var int|null
     *
     * @ORM\Column(name="level", type="integer")
     * @Gedmo\TreeLevel
     */
    #[ORM\Column(name: 'level', type: Types::INTEGER)]
    #[Gedmo\TreeLevel]
    private $level;

    /**
     * @var string|null
     */
    private $name;

    /**
     * @var CategoryClosure[]
     */
    private $closures = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setParent(self $parent = null): void
    {
        $this->parent = $parent;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function addClosure(CategoryClosure $closure): void
    {
        $this->closures[] = $closure;
    }

    public function setLevel(?int $level): void
    {
        $this->level = $level;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setFullName(?string $fullName): void
    {
        $this->fullName = $fullName;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }
}
