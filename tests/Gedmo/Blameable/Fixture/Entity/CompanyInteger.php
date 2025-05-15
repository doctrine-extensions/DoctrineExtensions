<?php

namespace Gedmo\Tests\Blameable\Fixture\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class CompanyInteger
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
     * @ORM\Column(name="name", type="string", length=128)
     */
    #[ORM\Column(name: 'name', type: Types::STRING, length: 128)]
    private ?string $name = null;

    /**
     * @var int|null
     *
     * @Gedmo\Blameable(on="create")
     * @ORM\Column(name="creator", type="integer")
     */
    #[ORM\Column(name: 'creator', type: Types::INTEGER)]
    #[Gedmo\Blameable(on: 'create')]
    private $creator;

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

    public function getCreator(): ?int
    {
        return $this->creator;
    }

    public function setCreator(?int $creator): void
    {
        $this->creator = $creator;
    }
}
