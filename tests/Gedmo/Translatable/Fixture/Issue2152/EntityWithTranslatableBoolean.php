<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Translatable\Fixture\Issue2152;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\Table("entity")
 */
#[ORM\Entity]
#[ORM\Table(name: 'entity')]
class EntityWithTranslatableBoolean
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(type="string", nullable=true)
     *
     * @var string|null
     */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Gedmo\Translatable]
    private $title;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(type="string", nullable=true)
     *
     * @var string|null
     */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Gedmo\Translatable]
    private $isOperating;

    /**
     * @var string
     *
     * @Gedmo\Locale
     */
    #[Gedmo\Locale]
    private $locale;

    public function __construct(string $title, string $isOperating = '0')
    {
        $this->translateInLocale('en', $title, $isOperating);
    }

    public function translateInLocale(string $locale, ?string $title, ?string $isOperating): void
    {
        $this->title = $title;
        $this->isOperating = $isOperating;
        $this->locale = $locale;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function isOperating(): ?string
    {
        return $this->isOperating;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }
}
