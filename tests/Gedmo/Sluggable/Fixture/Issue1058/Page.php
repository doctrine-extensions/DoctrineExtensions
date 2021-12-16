<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Fixture\Issue1058;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class Page
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
     * @ORM\Column(name="title", type="string", length=64)
     */
    #[ORM\Column(name: 'title', type: Types::STRING, length: 64)]
    private $title;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="Gedmo\Tests\Sluggable\Fixture\Issue1058\User")
     * @ORM\JoinColumn(nullable=false)
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $user;

    /**
     * @var string|null
     *
     * @Gedmo\Slug(separator="-", fields={"title"}, unique=true, unique_base="user")
     * @ORM\Column(name="slug", type="string", length=64)
     */
    #[Gedmo\Slug(separator: '-', unique: true, unique_base: 'user', fields: ['title'])]
    #[ORM\Column(name: 'slug', type: Types::STRING, length: 64)]
    private $slug;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
