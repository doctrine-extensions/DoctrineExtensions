<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Fixture\Issue827;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class Post
{
    /**
     * @var string|null
     *
     * @ORM\Id
     * @ORM\Column(name="title", unique=true, length=64)
     */
    #[ORM\Id]
    #[ORM\Column(name: 'title', unique: true, length: 64)]
    private $title;

    /**
     * @var string|null
     *
     * @ORM\Id
     * @Gedmo\Slug(updatable=true, unique=true, fields={"title"})
     * @ORM\Column(length=64, nullable=true)
     */
    #[ORM\Id]
    #[ORM\Column(length: 64, nullable: true)]
    #[Gedmo\Slug(updatable: true, unique: true, fields: ['title'])]
    private $slug;

    /**
     * @var Collection<int, Comment>
     *
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="post")
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'post')]
    private $comments;

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }
}
