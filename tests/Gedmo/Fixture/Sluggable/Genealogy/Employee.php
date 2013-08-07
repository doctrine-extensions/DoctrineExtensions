<?php

namespace Gedmo\Fixture\Sluggable\Genealogy;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 */
class Employee extends Man
{
    /**
     * @ORM\Column(length=64)
     */
    private $occupation;

    /**
     * @ORM\Column(length=128)
     * @Gedmo\Slug(fields={"name", "surname", "occupation", "region"})
     */
    private $workerSlug;

    public function setOccupation($occupation)
    {
        $this->occupation = $occupation;
        return $this;
    }

    public function getOccupation()
    {
        return $this->occupation;
    }

    public function setWorkerSlug($workerSlug)
    {
        $this->workerSlug = $workerSlug;
        return $this;
    }

    public function getWorkerSlug()
    {
        return $this->workerSlug;
    }
}
