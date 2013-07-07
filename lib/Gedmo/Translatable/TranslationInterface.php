<?php

namespace Gedmo\Translatable;

interface TranslationInterface
{
    /**
     * Get id
     *
     * @return integer $id
     */
    function getId();

    /**
     * Set locale
     *
     * @param string $locale
     */
    function setLocale($locale);

    /**
     * Get locale
     *
     * @return string $locale
     */
    function getLocale();

    /**
     * Set object related
     *
     * @param string $object
     */
    function setObject($object);

    /**
     * Get related object
     *
     * @return object $object
     */
    function getObject();
}
