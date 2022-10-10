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
class Person
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="name", type="string", length=128)
     */
    #[ORM\Column(name: 'name', type: Types::STRING, length: 128)]
    public $name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="desc", type="string", length=128)
     */
    #[ORM\Column(name: 'desc', type: Types::STRING, length: 128)]
    public $description;

    /**
     * @var string|null
     *
     * @ORM\Column(name="last_name", type="string", length=128, nullable=true)
     */
    #[ORM\Column(name: 'last_name', type: Types::STRING, length: 128, nullable: true)]
    public $lastName;

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
     * @var Collection<int, PersonTranslation>
     *
     * @ORM\OneToMany(targetEntity="PersonTranslation", mappedBy="translatable", cascade={"persist"})
     */
    #[ORM\OneToMany(targetEntity: PersonTranslation::class, mappedBy: 'translatable', cascade: ['persist'])]
    private $translations;

    /**
     * @var Person|null
     *
     * @ORM\ManyToOne(targetEntity="Person")
     */
    #[ORM\ManyToOne(targetEntity: self::class)]
    private $parent;

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

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $name): void
    {
        $this->lastName = $name;
    }

    public function setParent(self $parent): void
    {
        $this->parent = $parent;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    /**
     * @return self|\Gedmo\Translator\TranslationProxy
     */
    public function translate(string $locale = 'en')
    {
        if ('en' === $locale) {
            return $this;
        }

        return new \Gedmo\Translator\TranslationProxy($this,
            $locale, // Locale
            ['name', 'lastName'], // List of translatable properties
            PersonTranslation::class, // Translation entity class
            $this->translations // Translations collection property
        );
    }
}
