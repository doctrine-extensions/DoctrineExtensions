<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Loggable\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;
use Gedmo\Loggable\Loggable;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\Document(collection="articles")
 * @Gedmo\Loggable
 */
#[ODM\Document(collection: 'articles')]
#[Gedmo\Loggable]
class Article implements Loggable
{
    /**
     * @var string|null
     * @ODM\Id
     */
    #[ODM\Id]
    private $id;

    /**
     * @var string|null
     * @Gedmo\Versioned
     * @ODM\Field(type="string")
     */
    #[ODM\Field(type: Type::STRING)]
    #[Gedmo\Versioned]
    private $title;

    /**
     * @var Author|null
     * @ODM\EmbedOne(targetDocument="Gedmo\Tests\Loggable\Fixture\Document\Author")
     * @Gedmo\Versioned
     */
    #[ODM\EmbedOne(targetDocument: Author::class)]
    #[Gedmo\Versioned]
    private $author;

    public function __toString()
    {
        return $this->title;
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

    public function setAuthor(?Author $author): void
    {
        $this->author = $author;
    }

    public function getAuthor(): ?Author
    {
        return $this->author;
    }
}
