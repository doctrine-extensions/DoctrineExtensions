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
use Symfony\Component\Clock\DatePoint;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class CommentDatePoint implements Timestampable
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    /**
     * @ORM\Column(name="message", type="text")
     */
    #[ORM\Column(name: 'message', type: Types::TEXT)]
    private ?string $message = null;

    /**
     * @var ArticleDatepoint|null
     *
     * @ORM\ManyToOne(targetEntity="Gedmo\Tests\Timestampable\Fixture\ArticleDatepoint", inversedBy="comments")
     */
    #[ORM\ManyToOne(targetEntity: ArticleDatepoint::class, inversedBy: 'comments')]
    private $article;

    /**
     * @ORM\Column(type="integer")
     */
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $status = null;

    /**
     * @var DatePoint|null
     *
     * @ORM\Column(name="closed", type="date_point", nullable=true)
     *
     * @Gedmo\Timestampable(on="change", field="status", value=1)
     */
    #[ORM\Column(name: 'closed', type: 'date_point', nullable: true)]
    #[Gedmo\Timestampable(on: 'change', field: 'status', value: 1)]
    private $closed;

    /**
     * @var DatePoint|null
     *
     * @ORM\Column(name="modified", type="date_point")
     *
     * @Gedmo\Timestampable(on="update")
     */
    #[ORM\Column(name: 'modified', type: 'date_point')]
    #[Gedmo\Timestampable(on: 'update')]
    private $modified;

    /**
     * @param ArticleDatepoint|null $article
     */
    public function setArticle(?ArticleDatepoint $article): void
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

    public function getModified(): ?DatePoint
    {
        return $this->modified;
    }

    public function getClosed(): ?DatePoint
    {
        return $this->closed;
    }
}
