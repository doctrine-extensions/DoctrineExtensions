<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Fixture\Yaml;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Category extends BaseCategory
{
    /**
     * @var int
     */
    private $id;

    private ?string $title = null;

    private ?string $slug = null;

    /**
     * @var Collection<int, self>
     */
    private $children;

    private ?Category $parent = null;

    /**
     * @var \DateTime
     */
    private $changed;

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
