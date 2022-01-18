<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Fixture\Issue1151;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\Document
 */
#[ODM\Document]
class Article
{
    /**
     * @var string|null
     *
     * @ODM\Id(strategy="NONE")
     */
    #[ODM\Id(strategy: 'NONE')]
    private $id;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     */
    #[ODM\Field(type: Type::STRING)]
    private $title;

    /**
     * @var string|null
     *
     * @Gedmo\Slug(separator="-", updatable=true, fields={"title"})
     * @ODM\Field(type="string")
     */
    #[Gedmo\Slug(separator: '-', updatable: true, fields: ['title'])]
    #[ODM\Field(type: Type::STRING)]
    private $slug;

    public function setId(?string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }
}
