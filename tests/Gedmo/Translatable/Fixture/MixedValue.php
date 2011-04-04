<?php

namespace Translatable\Fixture;

/**
 * @Entity
 */
class MixedValue
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    private $id;

    /**
     * @gedmo:Translatable
     * @Column(type="datetime")
     */
    private $date;

    /**
     * @gedmo:Translatable
     * @Column(type="custom")
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