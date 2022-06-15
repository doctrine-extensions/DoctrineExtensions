<?php

declare(strict_types=1);

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
     * @var int|null
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    /**
     * @var \DateTime|null
     *
     * @Gedmo\Translatable
     * @ORM\Column(type="datetime")
     */
    #[Gedmo\Translatable]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private $date;

    /**
     * @var mixed
     *
     * @Gedmo\Translatable
     * @ORM\Column(type="custom")
     */
    #[Gedmo\Translatable]
    #[ORM\Column(type: 'custom')]
    private $cust;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setDate(\DateTime $date): void
    {
        $this->date = $date;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    /**
     * @param mixed $cust
     */
    public function setCust($cust): void
    {
        $this->cust = $cust;
    }

    /**
     * @return mixed
     */
    public function getCust()
    {
        return $this->cust;
    }
}
