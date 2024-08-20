<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Tree\Fixture\Issue2616;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="page")
 */
#[ORM\Entity, ORM\Table(name: 'page')]
class Page
{
    /**
     * @var Category|null
     *
     * @ORM\OneToOne(targetEntity="Category", inversedBy="page")
     * @ORM\JoinColumn(name="entity_id", referencedColumnName="category_id", nullable=false)
     */
    #[ORM\JoinColumn(name: 'entity_id', referencedColumnName: 'category_id', nullable: false)]
    #[ORM\OneToOne(targetEntity: Category::class, inversedBy: 'page')]
    protected $category;
    /**
     * @var int|null
     *
     * @ORM\Column(name="page_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    #[ORM\Column(name: 'page_id', type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    #[ORM\Id]
    private $id;

    /**
     * @ORM\Column(name="title", type="string", length=64)
     */
    #[ORM\Column(name: 'title', type: Types::STRING, length: 64)]
    private ?string $title = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): void
    {
        $this->category = $category;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }
}
