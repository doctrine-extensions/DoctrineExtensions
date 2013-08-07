<?php

namespace Gedmo\Fixture\Sortable;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Sortable\Entity\Repository\SortableRepository")
 */
class SimpleListItem
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(length=255)
     */
    private $name;

    /**
     * @Gedmo\Sortable
     * @ORM\Column(type="integer")
     */
    private $position;

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

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function getPosition()
    {
        return $this->position;
    }
}
