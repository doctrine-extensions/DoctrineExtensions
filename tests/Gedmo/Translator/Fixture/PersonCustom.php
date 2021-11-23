<?php

namespace Gedmo\Tests\Translator\Fixture;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class PersonCustom
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(name="name", type="string", length=128)
     */
    private $name;

    /**
     * @ORM\Column(name="desc", type="string", length=128)
     */
    private $description;

    //
    // TRANSLATIONS DEFINITION:
    //

    /**
     * @ORM\OneToMany(targetEntity="PersonCustomTranslation", mappedBy="translatable", cascade={"persist"})
     */
    private $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function translate($locale = null)
    {
        if (null === $locale) {
            return $this;
        }

        return new CustomProxy($this,
        /* Locale                            */ $locale,
        /* List of translatable properties:  */ ['name'],
        /* Translation entity class:         */ PersonCustomTranslation::class,
        /* Translations collection property: */ $this->translations
        );
    }
}
