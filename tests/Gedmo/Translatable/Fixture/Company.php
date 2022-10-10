<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Translatable\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class Company implements Translatable
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
     * @ORM\Column(name="title", type="string", length=128)
     * @Gedmo\Translatable
     */
    #[Gedmo\Translatable]
    #[ORM\Column(name: 'title', type: Types::STRING, length: 128)]
    private $title;

    /**
     * @var CompanyEmbedLink
     * @ORM\Embedded(class="Gedmo\Tests\Translatable\Fixture\CompanyEmbedLink")
     */
    #[ORM\Embedded(class: CompanyEmbedLink::class)]
    private $link;

    /**
     * @var string|null
     *
     * Used locale to override Translation listener`s locale
     *
     * @Gedmo\Locale
     */
    #[Gedmo\Locale]
    private $locale;

    public function __construct()
    {
        $this->link = new CompanyEmbedLink();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getLink(): CompanyEmbedLink
    {
        return $this->link;
    }

    public function setLink(CompanyEmbedLink $link): self
    {
        $this->link = $link;

        return $this;
    }

    public function setTranslatableLocale(?string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }
}
