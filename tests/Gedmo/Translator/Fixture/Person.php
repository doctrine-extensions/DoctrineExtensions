<?php

namespace Translator\Fixture;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 */
class Person
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
    public $name;

    /**
     * @ORM\Column(name="desc", type="string", length=128)
     */
    public $description;

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
     * @ORM\OneToMany(targetEntity="PersonTranslation", mappedBy="translatable", cascade={"persist"})
     */
    private $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    public function translate($locale = 'en')
    {
        if ('en' === $locale) {
            return $this;
        }

        return new \Gedmo\Translator\TranslationProxy($this,
        /* Locale                            */ $locale,
        /* List of translatable properties:  */ array('name'),
        /* Translation entity class:         */ 'Translator\Fixture\PersonTranslation',
        /* Translations collection property: */ $this->translations
        );
    }
}
