<?php

namespace Gedmo\Translatable;

interface TranslationInterface
{
    /**
     * Get id
     *
     * @return integer
     */
    public function getId();

    /**
     * Set locale
     *
     * @param string $locale
     */
    public function setLocale($locale);

    /**
     * Get locale
     *
     * @return string
     */
    public function getLocale();

    /**
     * Set object related
     *
     * @param object $object
     */
    public function setObject($object);

    /**
     * Get related object
     *
     * @return object
     */
    public function getObject();
}
