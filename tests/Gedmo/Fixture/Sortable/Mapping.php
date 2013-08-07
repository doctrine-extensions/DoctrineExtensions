<?php

namespace Gedmo\Fixture\Sortable;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Mapping
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(length=32)
     */
    private $username;


    /**
     * @ORM\Column(length=256)
     */
    private $email;

    /**
     * @ORM\Column(length=64)
     */
    private $occupation;

    /**
     * @ORM\Column(length=32, nullable=true)
     */
    private $company;

    /**
     * @Gedmo\Sortable
     * @ORM\Column(type="integer")
     */
    private $position;

    /**
     * @Gedmo\Sortable(groups={"company", "occupation"})
     * @ORM\Column(type="integer")
     */
    private $sortedByOccupation;
}
