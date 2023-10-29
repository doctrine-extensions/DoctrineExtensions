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
class SimpleArticle
{
    /**
     * @var string|null
     *
     * @MongoODM\Id
     */
    #[MongoODM\Id]
    private $id;

    /**
     * @Gedmo\Translatable
     *
     * @MongoODM\Field(type="string")
     */
    #[Gedmo\Translatable]
    #[MongoODM\Field(type: Type::STRING)]
    private ?string $title = null;

    /**
     * @Gedmo\Translatable
     *
     * @MongoODM\Field(type="string")
     */
    #[Gedmo\Translatable]
    #[MongoODM\Field(type: Type::STRING)]
    private ?string $content = null;

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

    public function setContent(?string $content): void
    {
        $this->content = $content;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }
}
