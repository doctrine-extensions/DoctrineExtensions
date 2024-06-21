<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Loggable\Fixture\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;
use Gedmo\Loggable\Loggable;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\Document(collection="articles")
 *
 * @Gedmo\Loggable
 */
#[ODM\Document(collection: 'articles')]
#[Gedmo\Loggable]
class Article implements Loggable
{
    /**
     * @var string|null
     *
     * @ODM\Id
     */
    #[ODM\Id]
    private $id;

    /**
     * @Gedmo\Versioned
     *
     * @ODM\Field(type="string")
     */
    #[ODM\Field(type: Type::STRING)]
    #[Gedmo\Versioned]
    private ?string $title = null;

    /**
     * @ODM\EmbedOne(targetDocument="Gedmo\Tests\Loggable\Fixture\Document\Author")
     *
     * @Gedmo\Versioned
     */
    #[ODM\EmbedOne(targetDocument: Author::class)]
    #[Gedmo\Versioned]
    private ?Author $author = null;

    /**
     * @var ?ArrayCollection<array-key, Reference>
     *
     * @ODM\EmbedMany(targetDocument="Gedmo\Tests\Loggable\Fixture\Document\Reference")
     *
     * @Gedmo\Versioned
     */
    #[ODM\EmbedMany(targetDocument: Reference::class)]
    #[Gedmo\Versioned]
    private ?ArrayCollection $references;

    public function __construct()
    {
        $this->references = new ArrayCollection();
    }

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

    /**
     * @param ?ArrayCollection<array-key, Reference> $references
     */
    public function setReferences(?ArrayCollection $references): void
    {
        $this->references = $references;
    }

    /**
     * @return ?ArrayCollection<array-key, Reference>
     */
    public function getReferences(): ?ArrayCollection
    {
        return $this->references;
    }
}
