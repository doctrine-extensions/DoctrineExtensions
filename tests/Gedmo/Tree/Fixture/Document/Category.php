<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Tree\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tree\Document\MongoDB\Repository\MaterializedPathRepository;

#[ODM\Document(repositoryClass: MaterializedPathRepository::class)]
#[Gedmo\Tree(type: 'materializedPath')]
class Category
{
    /**
     * @var string|null
     */
    #[ODM\Id]
    private $id;

    #[ODM\Field(type: Type::STRING)]
    #[Gedmo\TreePathSource]
    private ?string $title = null;

    /**
     * @var string|null
     */
    #[ODM\Field(type: Type::STRING)]
    #[Gedmo\TreePath(separator: '|')]
    private $path;

    #[ODM\ReferenceOne(targetDocument: self::class)]
    #[Gedmo\TreeParent]
    private ?Category $parent = null;

    /**
     * @var int|null
     */
    #[ODM\Field(type: Type::INT)]
    #[Gedmo\TreeLevel]
    private $level;

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

    public function setParent(?self $parent = null): void
    {
        $this->parent = $parent;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }
}
