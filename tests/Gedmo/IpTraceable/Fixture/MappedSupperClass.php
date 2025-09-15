<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\IpTraceable\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\MappedSuperclass
 */
#[ORM\MappedSuperclass]
class MappedSupperClass
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    protected ?int $id = null;

    /**
     * @Gedmo\Locale
     */
    #[Gedmo\Locale]
    protected ?string $locale = null;

    /**
     * @Gedmo\Translatable
     *
     * @ORM\Column(name="name", type="string", length=191)
     */
    #[Gedmo\Translatable]
    #[ORM\Column(name: 'name', type: Types::STRING, length: 191)]
    protected ?string $name = null;

    /**
     * @ORM\Column(name="created_at", type="string", length=45)
     *
     * @Gedmo\IpTraceable(on="create")
     */
    #[ORM\Column(name: 'created_at', type: Types::STRING, length: 45)]
    #[Gedmo\IpTraceable(on: 'create')]
    protected ?string $createdFromIp = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getCreatedFromIp(): ?string
    {
        return $this->createdFromIp;
    }
}
