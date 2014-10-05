<?php

namespace Translator\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

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

    public function translate($locale = null)
    {
        if (null === $locale) {
            return $this;
        }

        return new CustomProxy($this,
        /* Locale                            */ $locale,
        /* List of translatable properties:  */ array('name'),
        /* Translation entity class:         */ 'Translator\Fixture\PersonCustomTranslation',
        /* Translations collection property: */ $this->translations
        );
    }
}
