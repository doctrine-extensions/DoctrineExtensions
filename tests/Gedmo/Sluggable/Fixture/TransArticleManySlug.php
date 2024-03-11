<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sluggable\Sluggable;
use Gedmo\Translatable\Translatable;

#[ORM\Entity]
class TransArticleManySlug implements Sluggable, Translatable
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    private ?int $page = null;

    #[ORM\Column(type: Types::STRING, length: 64)]
    #[Gedmo\Translatable]
    private ?string $title = null;

    #[ORM\Column(type: Types::STRING, length: 64)]
    private ?string $uniqueTitle = null;

    /**
     * @var string|null
     */
    #[Gedmo\Slug(fields: ['uniqueTitle'])]
    #[ORM\Column(type: Types::STRING, length: 128)]
    private ?string $uniqueSlug = null;

    #[ORM\Column(type: Types::STRING, length: 16)]
    #[Gedmo\Translatable]
    private ?string $code = null;

    /**
     * @var string|null
     */
    #[ORM\Column(type: Types::STRING, length: 128)]
    #[Gedmo\Slug(fields: ['title', 'code'])]
    #[Gedmo\Translatable]
    private ?string $slug = null;

    /**
     * Used locale to override Translation listener`s locale
     */
    #[Gedmo\Locale]
    private ?string $locale = null;

    public function setPage(?int $page): void
    {
        $this->page = $page;
    }

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

    public function setUniqueTitle(?string $uniqueTitle): void
    {
        $this->uniqueTitle = $uniqueTitle;
    }

    public function getUniqueTitle(): ?string
    {
        return $this->uniqueTitle;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function getUniqueSlug(): ?string
    {
        return $this->uniqueSlug;
    }

    public function setTranslatableLocale(?string $locale): void
    {
        $this->locale = $locale;
    }
}
