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
 * @ODM\Document
 *
 * @Gedmo\Loggable
 */
#[ODM\Document]
#[Gedmo\Loggable]
class RelatedArticle implements Loggable
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
     * @Gedmo\Versioned
     *
     * @ODM\Field(type="string")
     */
    #[ODM\Field(type: Type::STRING)]
    #[Gedmo\Versioned]
    private ?string $content = null;

    /**
     * @var ?ArrayCollection<array-key, Comment>
     *
     * @ODM\ReferenceMany(targetDocument="Gedmo\Tests\Loggable\Fixture\Document\Comment", mappedBy="article")
     */
    #[ODM\ReferenceMany(targetDocument: Comment::class, mappedBy: 'article')]
    private ?ArrayCollection $comments = null;

    /**
     * @var ?ArrayCollection<array-key, Reference>
     *
     * @ODM\EmbedMany(targetDocument="Gedmo\Tests\Loggable\Fixture\Document\Reference")
     *
     * @Gedmo\Versioned
     */
    #[ODM\EmbedMany(targetDocument: Reference::class)]
    #[Gedmo\Versioned]
    private ?ArrayCollection $references = null;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->references = new ArrayCollection();
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

    public function setContent(?string $content): void
    {
        $this->content = $content;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function addComment(Comment $comment): void
    {
        $comment->setArticle($this);
        $this->comments[] = $comment;
    }

    /**
     * @return ?ArrayCollection<array-key, Comment>
     */
    public function getComments(): ?ArrayCollection
    {
        return $this->comments;
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
