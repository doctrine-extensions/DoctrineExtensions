<?php

namespace Fixture\Sluggable\Genealogy;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 */
class Man extends Person
{
    /**
     * @ORM\Column(length=64)
     */
    private $surname;

    /**
     * @ORM\Column(length=128)
     * @Gedmo\Slug(fields={"name", "surname", "region"})
     */
    private $slug;

    public function setSurname($surname)
    {
        $this->surname = $surname;
        return $this;
    }

    public function getSurname()
    {
        return $this->surname;
    }

    public function setSlug($slug)
    {
        $this->slug = $slug;
        return $this;
    }

    public function getSlug()
    {
        return $this->slug;
    }
}
