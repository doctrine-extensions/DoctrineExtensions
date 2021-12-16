<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Fixture\Issue827;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class Article
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
     * @ORM\Column(name="title", length=64)
     */
    #[ORM\Column(name: 'title', length: 64)]
    private $title;

    /**
     * @var Category|null
     *
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="articles")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id", nullable=false)
     */
    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'articles')]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: false)]
    private $category;

    /**
     * @var string|null
     *
     * @Gedmo\Slug(updatable=true, unique=true, unique_base="category", fields={"title"})
     * @ORM\Column(length=64, nullable=true)
     */
    #[Gedmo\Slug(updatable: true, unique: true, unique_base: 'category', fields: ['title'])]
    #[ORM\Column(length: 64, nullable: true)]
    private $slug;

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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setCategory(Category $category): void
    {
        $this->category = $category;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }
}
