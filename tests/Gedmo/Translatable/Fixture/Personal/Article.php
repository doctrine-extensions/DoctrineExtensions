<?php

namespace Gedmo\Tests\Translatable\Fixture\Personal;

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
     * @ORM\Column(length=128)
     */
    #[ORM\Column(length: 128)]
    #[Gedmo\Translatable]
    private $title;

    /**
     * @ORM\OneToMany(targetEntity="PersonalArticleTranslation", mappedBy="object")
     */
    #[ORM\OneToMany(targetEntity: PersonalArticleTranslation::class, mappedBy: 'object')]
    private $translations;

    public function getTranslations()
    {
        return $this->translations;
    }

    public function addTranslation(PersonalArticleTranslation $t)
    {
        if (!$this->translations->contains($t)) {
            $this->translations[] = $t;
            $t->setObject($this);
        }
    }

    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }
}
