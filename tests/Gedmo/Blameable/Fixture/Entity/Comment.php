<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Blameable\Fixture\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Blameable;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class Comment implements Blameable
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
     * @var string|null
     *
     * @ORM\Column(name="message", type="text")
     */
    #[ORM\Column(name: 'message', type: Types::TEXT)]
    private $message;

    /**
     * @var Article|null
     *
     * @ORM\ManyToOne(targetEntity="Gedmo\Tests\Blameable\Fixture\Entity\Article", inversedBy="comments")
     */
    #[ORM\ManyToOne(targetEntity: Article::class, inversedBy: 'comments')]
    private $article;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer")
     */
    #[ORM\Column(type: Types::INTEGER)]
    private $status;

    /**
     * @var string|null
     *
     * @ORM\Column(name="closed", type="string", nullable=true)
     * @Gedmo\Blameable(on="change", field="status", value=1)
     */
    #[ORM\Column(name: 'closed', type: Types::STRING, nullable: true)]
    #[Gedmo\Blameable(on: 'change', field: 'status', value: 1)]
    private $closed;

    /**
     * @var string|null
     *
     * @ORM\Column(name="modified", type="string")
     * @Gedmo\Blameable(on="update")
     */
    #[ORM\Column(name: 'modified', type: Types::STRING)]
    #[Gedmo\Blameable(on: 'update')]
    private $modified;

    public function setArticle(?Article $article): void
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

    public function getModified(): ?string
    {
        return $this->modified;
    }

    public function getClosed(): ?string
    {
        return $this->closed;
    }
}
