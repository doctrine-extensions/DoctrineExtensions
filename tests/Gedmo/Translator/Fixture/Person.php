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
use Gedmo\Translator\TranslationInterface;
use Gedmo\Translator\TranslationProxy;

#[ORM\Entity]
class Person
{
    /**
     * @var string|null
     */
    #[ORM\Column(name: 'name', type: Types::STRING, length: 128)]
    public ?string $name = null;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'desc', type: Types::STRING, length: 128)]
    public ?string $description = null;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'last_name', type: Types::STRING, length: 128, nullable: true)]
    public ?string $lastName = null;

    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * @var Collection<int, TranslationInterface>
     */
    #[ORM\OneToMany(targetEntity: PersonTranslation::class, mappedBy: 'translatable', cascade: ['persist'])]
    private Collection $translations;

    #[ORM\ManyToOne(targetEntity: self::class)]
    private ?Person $parent = null;

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
     * @return self|TranslationProxy
     */
    public function translate(string $locale = 'en')
    {
        if ('en' === $locale) {
            return $this;
        }

        return new TranslationProxy(
            $this,
            $locale, // Locale
            ['name', 'lastName'], // List of translatable properties
            PersonTranslation::class, // Translation entity class
            $this->translations // Translations collection property
        );
    }
}
