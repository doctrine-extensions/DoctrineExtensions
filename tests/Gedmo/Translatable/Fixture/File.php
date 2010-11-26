<?php

namespace Translatable\Fixture;

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discriminator", type="string")
 * @DiscriminatorMap({"file" = "File", "image" = "Image"})
 */
class File
{
    /** 
     * @Id 
     * @GeneratedValue 
     * @Column(type="integer")
     */
    private $id;
    
    /**
     * @gedmo:Translatable
     * @Column(length=128)
     */
    private $name;
    
    /**
     * @Column(type="integer")
     */
    private $size;
    
    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
    
    public function setSize($size)
    {
        $this->size = $size;
    }

    public function getSize()
    {
        return $this->size;
    }
}