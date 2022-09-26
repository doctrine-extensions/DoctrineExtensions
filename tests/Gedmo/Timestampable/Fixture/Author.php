<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Timestampable\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable
 */
#[ORM\Embeddable]
class Author
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="author_name", type="string", length=128, nullable=true)
     */
    #[ORM\Column(name: 'author_name', type: Types::STRING, length: 128, nullable: true)]
    private $name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="author_email", type="string", length=50, nullable=true)
     */
    #[ORM\Column(name: 'author_email', type: Types::STRING, length: 50, nullable: true)]
    private $email;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }
}
