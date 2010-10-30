<?php

namespace Translatable\Fixture;

use DoctrineExtensions\Translatable\Translatable;

/**
 * @Entity
 */
class Person implements Translatable
{
    /** 
     * @Id 
     * @GeneratedValue 
     * @Column(type="integer")
     */
    private $id;

    /**
     * @Column(name="name", type="string", length=128)
     */
    private $name;

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
    
    public function getTranslatableFields()
    {
        return array('name');
    }
    
    public function getTranslatableLocale()
    {
        return null;
    }
    
    public function getTranslationEntity()
    {
        return 'Translatable\Fixture\PersonTranslation';
    }
}