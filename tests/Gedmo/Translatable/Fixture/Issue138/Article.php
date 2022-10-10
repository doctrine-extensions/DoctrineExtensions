<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Translatable\Fixture\Issue138;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class Article
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
     * @Gedmo\Translatable
     * @ORM\Column(length=128)
     */
    #[Gedmo\Translatable]
    #[ORM\Column(length: 128)]
    private $title;

    /**
     * @var string|null
     *
     * @Gedmo\Translatable
     * @ORM\Column(length=128)
     */
    #[Gedmo\Translatable]
    #[ORM\Column(length: 128)]
    private $titleTest;

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

    public function setTitleTest(?string $titleTest): void
    {
        $this->titleTest = $titleTest;
    }

    public function getTitleTest(): ?string
    {
        return $this->titleTest;
    }
}
