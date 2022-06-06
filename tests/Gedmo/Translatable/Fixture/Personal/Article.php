<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Translatable\Fixture\Personal;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @Gedmo\TranslationEntity(class="Gedmo\Tests\Translatable\Fixture\Personal\PersonalArticleTranslation")
 * @ORM\Entity
 */
#[ORM\Entity]
#[Gedmo\TranslationEntity(class: PersonalArticleTranslation::class)]
class Article
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
     * @Gedmo\Translatable
     * @ORM\Column(length=128)
     */
    #[ORM\Column(length: 128)]
    #[Gedmo\Translatable]
    private $title;

    /**
     * @var Collection<int, PersonalArticleTranslation>
     *
     * @ORM\OneToMany(targetEntity="PersonalArticleTranslation", mappedBy="object")
     */
    #[ORM\OneToMany(targetEntity: PersonalArticleTranslation::class, mappedBy: 'object')]
    private $translations;

    /**
     * @return Collection<int, PersonalArticleTranslation>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(PersonalArticleTranslation $t): void
    {
        if (!$this->translations->contains($t)) {
            $this->translations[] = $t;
            $t->setObject($this);
        }
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
}
