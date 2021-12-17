<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\SoftDeleteable\Fixture\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class OtherComment
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="comment", type="string")
     */
    #[ORM\Column(name: 'comment', type: Types::STRING)]
    private $comment;

    /**
     * @var OtherArticle|null
     *
     * @ORM\ManyToOne(targetEntity="OtherArticle", inversedBy="comments")
     */
    #[ORM\ManyToOne(targetEntity: OtherArticle::class, inversedBy: 'comments')]
    private $article;

    /**
     * @var \DateTimeInterface|null
     */
    private $deletedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setComment(?string $comment): void
    {
        $this->comment = $comment;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setArticle(OtherArticle $article): void
    {
        $this->article = $article;
    }

    public function getArticle(): ?OtherArticle
    {
        return $this->article;
    }
}
