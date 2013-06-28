<?php

namespace Gedmo\Translatable\Document\MappedSuperclass;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoODM;

/**
 * @MongoODM\MappedSuperclass
 */
abstract class AbstractTranslation
{
    /**
     * @var integer $id
     *
     * @MongoODM\Id
     */
    protected $id;

    /**
     * @var string $locale
     *
     * @MongoODM\String
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
     * @return AbstractPersonalTranslation
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
     * @param object $object
     * @return AbstractPersonalTranslation
     */
    public function setObject($object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * Get object related
     *
     * @return string $object
     */
    public function getObject()
    {
        return $this->object;
    }
}

