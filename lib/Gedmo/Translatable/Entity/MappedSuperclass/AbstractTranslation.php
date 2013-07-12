<?php

namespace Gedmo\Translatable\Entity\MappedSuperclass;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\TranslationInterface;

/**
 * @ORM\MappedSuperclass
 */
abstract class AbstractTranslation implements TranslationInterface
{

    /**
     * @var integer $id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var string $locale
     *
     * @ORM\Column(type="string", length=8)
     */
    protected $locale;

    /**
     * Related entity with ManyToOne relation
     * must be mapped by user
     */
    protected $object;

    /**
     * Get id
     *
     * @return integer $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set locale
     *
     * @param string $locale
     * @return AbstractTranslation
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * Get locale
     *
     * @return string $locale
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set object related
     *
     * @param string $object
     * @return AbstractTranslation
     */
    public function setObject($object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * Get related object
     *
     * @return object $object
     */
    public function getObject()
    {
        return $this->object;
    }
}
