<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Fixture\Document\Handler;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sluggable\Handler\TreeSlugHandler;

#[ODM\Document]
class TreeSlug
{
    /**
     * @var string|null
     */
    #[ODM\Id]
    private $id;

    #[ODM\Field(type: Type::STRING)]
    private ?string $title = null;

    /**
     * @var string|null
     */
    #[Gedmo\Slug(separator: '-', updatable: true, fields: ['title'])]
    #[Gedmo\SlugHandler(class: TreeSlugHandler::class, options: ['parentRelationField' => 'parent', 'separator' => '/'])]
    #[ODM\Field(type: Type::STRING)]
    private $alias;

    #[ODM\ReferenceOne(targetDocument: self::class)]
    private ?TreeSlug $parent = null;

    public function setParent(?self $parent = null): void
    {
        $this->parent = $parent;
    }

    public function getParent(): ?self
    {
        return $this->parent;
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

    public function getSlug(): ?string
    {
        return $this->alias;
    }
}
