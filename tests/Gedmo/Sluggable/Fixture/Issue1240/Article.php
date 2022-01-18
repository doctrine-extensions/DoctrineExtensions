<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Fixture\Issue1240;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sluggable\Sluggable;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class Article implements Sluggable
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
     * @var string|null
     *
     * @Gedmo\Slug(separator="+", updatable=true, fields={"title"})
     * @ORM\Column(name="slug", type="string", length=64, unique=true)
     */
    #[Gedmo\Slug(separator: '+', updatable: true, fields: ['title'])]
    #[ORM\Column(name: 'slug', type: Types::STRING, length: 64, unique: true)]
    private $slug;

    /**
     * @var string|null
     *
     * @Gedmo\Slug(separator="+", updatable=true, fields={"title"}, style="camel")
     * @ORM\Column(name="camel_slug", type="string", length=64, unique=true)
     */
    #[ORM\Column(name: 'camel_slug', type: Types::STRING, length: 64, unique: true)]
    #[Gedmo\Slug(separator: '+', updatable: true, fields: ['title'], style: 'camel')]
    private $camelSlug;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setSlug(?string $slug): void
    {
        $this->slug = $slug;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function getCamelSlug(): ?string
    {
        return $this->camelSlug;
    }

    public function setCamelSlug(?string $camelSlug): void
    {
        $this->camelSlug = $camelSlug;
    }
}
