<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Translatable\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoODM;
use Doctrine\ODM\MongoDB\Types\Type;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @MongoODM\Document(collection="articles")
 */
#[MongoODM\Document(collection: 'articles')]
class Article
{
    /**
     * @var string|null
     *
     * @MongoODM\Id
     */
    #[MongoODM\Id]
    private $id;

    /**
     * @var string|null
     *
     * @Gedmo\Translatable
     * @MongoODM\Field(type="string")
     */
    #[Gedmo\Translatable]
    #[MongoODM\Field(type: Type::STRING)]
    private $title;

    /**
     * @var string|null
     *
     * @Gedmo\Translatable
     * @MongoODM\Field(type="string")
     */
    #[Gedmo\Translatable]
    #[MongoODM\Field(type: Type::STRING)]
    private $code;

    /**
     * @var string|null
     *
     * @Gedmo\Slug(fields={"title", "code"})
     * @Gedmo\Translatable
     * @MongoODM\Field(type="string")
     */
    #[Gedmo\Translatable]
    #[MongoODM\Field(type: Type::STRING)]
    #[Gedmo\Slug(fields: ['title', 'code'])]
    private $slug;

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

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }
}
