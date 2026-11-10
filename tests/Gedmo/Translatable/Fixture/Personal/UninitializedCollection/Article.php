<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Translatable\Fixture\Personal\UninitializedCollection;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Personal translation whose collection is a non-nullable typed property
 * and is intentionally not initialized in a constructor.
 *
 * @Gedmo\TranslationEntity(class="Gedmo\Tests\Translatable\Fixture\Personal\UninitializedCollection\ArticleTranslation")
 *
 * @ORM\Entity
 * @ORM\Table(name="uninitialized_personal_article")
 */
#[ORM\Entity]
#[ORM\Table(name: 'uninitialized_personal_article')]
#[Gedmo\TranslationEntity(class: ArticleTranslation::class)]
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
     * @Gedmo\Translatable
     *
     * @ORM\Column(length=128)
     */
    #[ORM\Column(length: 128)]
    #[Gedmo\Translatable]
    private ?string $title = null;

    /**
     * @var Collection<int, ArticleTranslation>
     *
     * @ORM\OneToMany(targetEntity="ArticleTranslation", mappedBy="object")
     */
    #[ORM\OneToMany(targetEntity: ArticleTranslation::class, mappedBy: 'object')]
    private Collection $translations;

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
