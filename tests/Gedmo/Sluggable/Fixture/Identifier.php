<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class Identifier
{
    /**
     * @var string|null
     *
     * @ORM\Id
     * @Gedmo\Slug(separator="_", updatable=false, fields={"title"})
     * @ORM\Column(length=32, unique=true)
     */
    #[ORM\Id]
    #[ORM\Column(length: 32, unique: true)]
    #[Gedmo\Slug(separator: '_', updatable: false, fields: ['title'])]
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(length=32)
     */
    #[ORM\Column(length: 32)]
    private $title;

    public function getId(): ?string
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
}
