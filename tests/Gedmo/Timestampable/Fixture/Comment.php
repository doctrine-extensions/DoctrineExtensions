<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Timestampable\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Timestampable;

#[ORM\Entity]
class Comment implements Timestampable
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: 'message', type: Types::TEXT)]
    private ?string $message = null;

    /**
     * @var Article|null
     */
    #[ORM\ManyToOne(targetEntity: Article::class, inversedBy: 'comments')]
    private ?Article $article = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $status = null;

    #[ORM\Column(name: 'closed', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'change', field: 'status', value: 1)]
    private ?\DateTimeInterface $closed = null;

    #[ORM\Column(name: 'modified', type: Types::TIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeInterface $modified = null;

    /**
     * @param Article|ArticleCarbon $article
     */
    public function setArticle(?Timestampable $article): void
    {
        $this->article = $article;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setStatus(?int $status): void
    {
        $this->status = $status;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setMessage(?string $message): void
    {
        $this->message = $message;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getModified(): ?\DateTimeInterface
    {
        return $this->modified;
    }

    public function getClosed(): ?\DateTimeInterface
    {
        return $this->closed;
    }
}
