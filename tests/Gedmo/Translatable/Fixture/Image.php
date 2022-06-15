<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Translatable\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class Image extends File
{
    /**
     * @var string|null
     *
     * @Gedmo\Translatable
     * @ORM\Column(length=128)
     */
    #[Gedmo\Translatable]
    #[ORM\Column(length: 128)]
    private $mime;

    public function setMime(?string $mime): void
    {
        $this->mime = $mime;
    }

    public function getMime(): ?string
    {
        return $this->mime;
    }
}
