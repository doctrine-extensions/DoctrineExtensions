<?php

namespace Translatable\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 */
class MixedValue
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(type="datetime")
     */
    private $date;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(type="custom")
     */
    private $cust;

    public function getId()
    {
        return $this->id;
    }

    public function setDate($date)
    {
        $this->date = $date;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setCust($cust)
    {
        $this->cust = $cust;
    }

    public function getCust()
    {
        return $this->cust;
    }
}
