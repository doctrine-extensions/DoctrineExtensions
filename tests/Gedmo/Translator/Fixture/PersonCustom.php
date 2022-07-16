<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Translator\Fixture;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class PersonCustom
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
     * @ORM\Column(name="name", type="string", length=128)
     */
    #[ORM\Column(name: 'name', type: Types::STRING, length: 128)]
    private $name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="desc", type="string", length=128)
     */
    #[ORM\Column(name: 'desc', type: Types::STRING, length: 128)]
    private $description;

    /**
     * @var Collection<int, PersonCustomTranslation>
     *
     * @ORM\OneToMany(targetEntity="PersonCustomTranslation", mappedBy="translatable", cascade={"persist"})
     */
    #[ORM\OneToMany(targetEntity: PersonCustomTranslation::class, mappedBy: 'translatable', cascade: ['persist'])]
    private $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

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

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return self|CustomProxy
     */
    public function translate(string $locale = null)
    {
        if (null === $locale) {
            return $this;
        }

        return new CustomProxy($this,
            $locale, // Locale
            ['name'], // List of translatable properties
            PersonCustomTranslation::class, // Translation entity class
            $this->translations // Translations collection property
        );
    }
}
