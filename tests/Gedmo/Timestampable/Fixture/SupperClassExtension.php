<?php

namespace Timestampable\Fixture;

/**
 * @Entity
 */
class SupperClassExtension extends MappedSupperClass
{
    /**
     * @Column(length=128)
     * @gedmo:Translatable
     */
    private $title;
    
    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }
}