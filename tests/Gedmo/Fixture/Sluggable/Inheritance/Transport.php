<?php

namespace Gedmo\Fixture\Sluggable\Inheritance;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\MappedSuperclass
 */
class Transport
{
    /**
     * @ORM\Column(length=32)
     */
    private $type;

    /**
     * @Gedmo\Slug(fields={"type"}, unique=true)
     * @ORM\Column(length=32, unique=true)
     */
    private $typeSlug;

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setTypeSlug($typeSlug)
    {
        $this->typeSlug = $typeSlug;
        return $this;
    }

    public function getTypeSlug()
    {
        return $this->typeSlug;
    }
}
