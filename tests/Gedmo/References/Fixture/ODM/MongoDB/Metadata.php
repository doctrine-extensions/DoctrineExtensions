<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\References\Fixture\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tests\References\Fixture\ORM\Category;

/**
 * Metadata of type Category
 */
#[ODM\EmbeddedDocument]
class Metadata
{
    /**
     * @var string|null
     */
    #[ODM\Field(type: Type::STRING)]
    private $name;

    #[Gedmo\ReferenceOne(type: 'entity', class: Category::class, identifier: 'categoryId')]
    private Category $category;

    #[ODM\Field(type: Type::INT)]
    private ?int $categoryId = null;

    public function __construct(Category $category)
    {
        $this->setCategory($category);
    }

    public function setCategoryId(int $categoryId): void
    {
        $this->categoryId = $categoryId;
    }

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function setCategory(Category $category): void
    {
        $this->category = $category;
        $this->categoryId = $category->getId();
    }

    public function getCategory(): Category
    {
        return $this->category;
    }
}
