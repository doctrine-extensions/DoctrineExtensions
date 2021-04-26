<?php

declare(strict_types=1);

namespace Translatable\Fixture\Issue2152;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\Table("entity")
 */
class EntityWithTranslatableBoolean
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    private $id;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(type="string", nullable=true)
     *
     * @var string|null
     */
    private $title;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(type="string", nullable=true)
     *
     * @var string|null
     */
    private $isOperating;

    /**
     * @var string
     *
     * @Gedmo\Locale()
     */
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
