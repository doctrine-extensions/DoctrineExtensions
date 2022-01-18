<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Tree\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as Mongo;
use Doctrine\ODM\MongoDB\Types\Type;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tree\Document\MongoDB\Repository\MaterializedPathRepository;

/**
 * @Mongo\Document(repositoryClass="Gedmo\Tree\Document\MongoDB\Repository\MaterializedPathRepository")
 * @Gedmo\Tree(type="materializedPath")
 */
#[Mongo\Document(repositoryClass: MaterializedPathRepository::class)]
#[Gedmo\Tree(type: 'materializedPath')]
class Category
{
    /**
     * @var string|null
     *
     * @Mongo\Id
     */
    #[Mongo\Id]
    private $id;

    /**
     * @var string|null
     *
     * @Mongo\Field(type="string")
     * @Gedmo\TreePathSource
     */
    #[Mongo\Field(type: Type::STRING)]
    #[Gedmo\TreePathSource]
    private $title;

    /**
     * @var string|null
     *
     * @Mongo\Field(type="string")
     * @Gedmo\TreePath(separator="|")
     */
    #[Mongo\Field(type: Type::STRING)]
    #[Gedmo\TreePath(separator: '|')]
    private $path;

    /**
     * @var self|null
     *
     * @Gedmo\TreeParent
     * @Mongo\ReferenceOne(targetDocument="Gedmo\Tests\Tree\Fixture\Document\Category")
     */
    #[Mongo\ReferenceOne(targetDocument: self::class)]
    #[Gedmo\TreeParent]
    private $parent;

    /**
     * @var int|null
     *
     * @Gedmo\TreeLevel
     * @Mongo\Field(type="int")
     */
    #[Mongo\Field(type: Type::INT)]
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

    public function setParent(self $parent = null): void
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
