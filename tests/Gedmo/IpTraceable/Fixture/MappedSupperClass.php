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
     * @var int|null
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    protected $id;

    /**
     * @var string|null
     *
     * @Gedmo\Locale
     */
    #[Gedmo\Locale]
    protected $locale;

    /**
     * @var string|null
     *
     * @Gedmo\Translatable
     *
     * @ORM\Column(name="name", type="string", length=191)
     */
    #[Gedmo\Translatable]
    #[ORM\Column(name: 'name', type: Types::STRING, length: 191)]
    protected $name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="created_at", type="string", length=45)
     *
     * @Gedmo\IpTraceable(on="create")
     */
    #[ORM\Column(name: 'created_at', type: Types::STRING, length: 45)]
    #[Gedmo\IpTraceable(on: 'create')]
    protected $createdFromIp;

    /**
     * @codeCoverageIgnore
     */
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
