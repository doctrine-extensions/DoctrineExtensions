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

#[ORM\Entity]
class Comment implements Blameable
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

    #[ORM\ManyToOne(targetEntity: Article::class, inversedBy: 'comments')]
    private ?Article $article = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $status = null;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'closed', type: Types::STRING, nullable: true)]
    #[Gedmo\Blameable(on: 'change', field: 'status', value: 1)]
    private ?string $closed = null;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'modified', type: Types::STRING)]
    #[Gedmo\Blameable(on: 'update')]
    private ?string $modified = null;

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
