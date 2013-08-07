<?php

namespace Gedmo\Fixture\Unmapped;

use Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation;

class PersonTranslation extends AbstractTranslation
{
    protected $object;
    private $bio;

    public function setBio($bio)
    {
        $this->bio = $bio;
        return $this;
    }

    public function getBio()
    {
        return $this->bio;
    }
}
