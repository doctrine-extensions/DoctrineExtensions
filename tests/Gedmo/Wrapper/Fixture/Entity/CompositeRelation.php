<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Wrapper\Fixture\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class CompositeRelation
{
    /**
     * @var Article
     *
     * @todo: add type hint when https://github.com/doctrine/orm/issues/8255 is solved
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Gedmo\Tests\Wrapper\Fixture\Entity\Article")
     */
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Article::class)]
    private $article;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    private int $status;

    /**
     * @ORM\Column(length=128)
     */
    #[ORM\Column(length: 128)]
    private ?string $title = null;

    public function __construct(Article $articleOne, int $status)
    {
        $this->article = $articleOne;
        $this->status = $status;
    }

    public function getArticle(): Article
    {
        return $this->article;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
