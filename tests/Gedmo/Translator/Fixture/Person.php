<?php

namespace Translator\Fixture;

use Doctrine\ORM\Mapping as ORM;

use Gedmo\Translator\ObjectTranslator;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
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
     * @ORM\OneToMany(targetEntity="PersonTranslation", mappedBy="translatable", cascade={"persist"})
     */
    private $translations;
    private $translator;

    public function __construct()
    {
        $this->translations = new ArrayCollection();

        $this->initializeTranslator();
    }

    /** @ORM\PrePersist */
    public function translateEntityToDefaultLocale()
    {
        $this->translator->translate();
    }

    /** @ORM\PostLoad */
    public function initializeTranslator()
    {
        if (null === $this->translator) {
            $this->translator = new ObjectTranslator($this,
            /* List of translatable properties:  */ array('name'),
            /* Translation entity class:         */ 'Translator\Fixture\PersonTranslation',
            /* Translations collection property: */ $this->translations
            );

            return;
        }

        $this->translateEntityToDefaultLocale();
    }

    public function translate($locale = null)
    {
        return $this->translator->translate($locale);
    }
}
