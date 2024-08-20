<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Fixture\Issue100;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sluggable\Sluggable;
use Gedmo\Translatable\Translatable;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class Article implements Sluggable, Translatable
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
     * @Gedmo\Translatable
     *
     * @ORM\Column(name="title", type="string", length=64)
     */
    #[ORM\Column(name: 'title', type: Types::STRING, length: 64)]
    #[Gedmo\Translatable]
    private ?string $title = null;

    /**
     * @Gedmo\Translatable
     * @Gedmo\Slug(separator="-", updatable=true, fields={"title"}, unique=true, uniqueOverTranslations=true)
     *
     * @ORM\Column(name="slug", type="string", length=64, unique=true)
     */
    #[Gedmo\Translatable]
    #[Gedmo\Slug(fields: ['title'], updatable: true, unique: true, uniqueOverTranslations: true, separator: '-')]
    #[ORM\Column(name: 'slug', type: Types::STRING, length: 64, unique: true)]
    private ?string $slug = null;

    /**
     * @Gedmo\Locale
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property
     */
    #[Gedmo\Locale]
    private ?string $locale = null;

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

    public function setSlug(?string $slug): void
    {
        $this->slug = $slug;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setTranslatableLocale(?string $locale): void
    {
        $this->locale = $locale;
    }
}
