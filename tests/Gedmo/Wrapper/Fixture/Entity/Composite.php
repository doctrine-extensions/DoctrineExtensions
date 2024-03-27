<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Wrapper\Fixture\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Composite
{
    #[ORM\Column(length: 128)]
    private ?string $title = null;

    public function __construct(#[ORM\Id]
        #[ORM\Column(type: Types::INTEGER)]
        private int $one, #[ORM\Id]
        #[ORM\Column(type: Types::INTEGER)]
        private int $two)
    {
    }

    public function getOne(): int
    {
        return $this->one;
    }

    public function getTwo(): int
    {
        return $this->two;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
