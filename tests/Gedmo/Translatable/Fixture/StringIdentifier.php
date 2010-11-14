<?php

namespace Translatable\Fixture;

/**
 * @Entity
 */
class StringIdentifier
{
    /** 
     * @Id 
     * @Column(name="uid", type="string", length=32)
     */
    private $uid;

    /**
     * @gedmo:Translatable
     * @Column(name="title", type="string", length=128)
     */
    private $title;
    
    /**
     * Used locale to override Translation listener`s locale
     * @gedmo:Locale
     */
    private $locale;

    public function getUid()
    {
        return $this->uid;
    }
    
    public function setUid($uid)
    {
        $this->uid = $uid;
    }
    
    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }
    
    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;
    }
}