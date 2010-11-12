<?php

namespace Translatable\Fixture;

/**
 * @Entity
 * @Translatable:Entity(class="Translatable\Fixture\PersonTranslation")
 */
class Person
{
    /** 
     * @Id 
     * @GeneratedValue 
     * @Column(type="integer")
     */
    private $id;

    /**
     * @Translatable:Field
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
}