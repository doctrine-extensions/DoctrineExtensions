<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Translatable\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class MixedValue
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(type="datetime")
     */
    #[Gedmo\Translatable]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private $date;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(type="custom")
     */
    #[Gedmo\Translatable]
    #[ORM\Column(type: 'custom')]
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
