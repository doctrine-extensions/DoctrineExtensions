<?php

namespace Gedmo\Tests\Translator\Fixture;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

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

    /**
     * @ORM\Column(name="last_name", type="string", length=128, nullable=true)
     */
    public $lastName;

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

    public function getLastName()
    {
        return $this->lastName;
    }

    public function setLastName($name)
    {
        $this->lastName = $name;
    }

    //
    // TRANSLATIONS DEFINITION:
    //

    /**
     * @ORM\OneToMany(targetEntity="PersonTranslation", mappedBy="translatable", cascade={"persist"})
     */
    private $translations;

    /**
     * @ORM\ManyToOne(targetEntity="Person")
     */
    private $parent;

    public function setParent(Person $parent)
    {
        $this->parent = $parent;
    }

    public function getParent()
    {
        return $this->parent;
    }

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
        /* List of translatable properties:  */ ['name', 'lastName'],
        /* Translation entity class:         */ PersonTranslation::class,
        /* Translations collection property: */ $this->translations
        );
    }
}
