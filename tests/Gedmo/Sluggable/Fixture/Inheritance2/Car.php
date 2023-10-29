<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Fixture\Inheritance2;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class Car extends Vehicle
{
    /**
     * @var string|null
     *
     * @ORM\Column(length=128)
     */
    #[ORM\Column(length: 128)]
    protected $title;

    /**
     * @ORM\Column(length=128)
     */
    #[ORM\Column(length: 128)]
    private ?string $description = null;

    /**
     * @var string|null
     *
     * @Gedmo\Slug(fields={"title"})
     *
     * @ORM\Column(length=128, unique=true)
     */
    #[Gedmo\Slug(fields: ['title'])]
    #[ORM\Column(length: 128, unique: true)]
    private $slug;

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

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
