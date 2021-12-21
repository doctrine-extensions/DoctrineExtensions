<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\ReferenceIntegrity\Fixture\Document\ManyNullify;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\Document(collection="types")
 */
class Type
{
    /**
     * @ODM\ReferenceMany(targetDocument="Gedmo\Tests\ReferenceIntegrity\Fixture\Document\ManyNullify\Article", mappedBy="type")
     * @Gedmo\ReferenceIntegrity("nullify")
     *
     * @var Collection<int, Article>
     */
    protected $articles;
    /**
     * @ODM\Id
     */
    private $id;

    /**
     * @ODM\Field(type="string")
     */
    private $title;

    /**
     * @ODM\Field(type="string")
     */
    private $identifier;

    public function __construct()
    {
        $this->articles = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $title
     */
    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $identifier
     */
    public function setIdentifier($identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function addArticle(Article $article): void
    {
        $this->articles[] = $article;
    }

    /**
     * @return Collection<int, Article> $articles
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }
}
