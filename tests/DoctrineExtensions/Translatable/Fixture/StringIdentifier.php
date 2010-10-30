<?php

namespace Translatable\Fixture;

use DoctrineExtensions\Translatable\Translatable;

/**
 * @Entity
 */
class StringIdentifier implements Translatable
{
    /** 
     * @Id 
     * @Column(name="uid", type="string", length=32)
     */
    private $uid;

    /**
     * @Column(name="title", type="string", length=128)
     */
    private $title;
    
    /*
     * Used locale to override Translation listener`s locale
     */
    private $_locale;

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
    
    public function getTranslatableFields()
    {
        return array('title');
    }
    
    public function setTranslatableLocale($locale)
    {
        $this->_locale = $locale;
    }
    
    public function getTranslatableLocale()
    {
        return $this->_locale;
    }
    
    public function getTranslationEntity()
    {
        return null;
    }
}