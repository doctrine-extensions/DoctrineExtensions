<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Loggable\Fixture\Document;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;
use Gedmo\Loggable\Loggable;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\Document
 * @Gedmo\Loggable
 */
#[ODM\Document]
#[Gedmo\Loggable]
class RelatedArticle implements Loggable
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
     * @var string|null
     * @Gedmo\Versioned
     * @ODM\Field(type="string")
     */
    #[ODM\Field(type: Type::STRING)]
    #[Gedmo\Versioned]
    private $content;

    /**
     * @var Collection<int, Comment>
     * @ODM\ReferenceMany(targetDocument="Gedmo\Tests\Loggable\Fixture\Document\Comment", mappedBy="article")
     */
    #[ODM\ReferenceMany(targetDocument: Comment::class, mappedBy: 'article')]
    private $comments;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function addComment(Comment $comment): void
    {
        $comment->setArticle($this);
        $this->comments[] = $comment;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
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
