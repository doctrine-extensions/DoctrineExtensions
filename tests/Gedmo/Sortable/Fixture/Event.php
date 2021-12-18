<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sortable\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sortable\Entity\Repository\SortableRepository;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Sortable\Entity\Repository\SortableRepository")
 */
#[ORM\Entity(repositoryClass: SortableRepository::class)]
class Event
{
    /**
     * @var int
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
     * @var \DateTime
     *
     * @Gedmo\SortableGroup
     * @ORM\Column(type="datetime")
     */
    #[Gedmo\SortableGroup]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private $dateTime;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=191)
     */
    #[ORM\Column(type: Types::STRING, length: 191)]
    private $name;

    /**
     * @var int
     *
     * @Gedmo\SortablePosition
     * @ORM\Column(type="integer")
     */
    #[Gedmo\SortablePosition]
    #[ORM\Column(type: Types::INTEGER)]
    private $position;

    public function getId()
    {
        return $this->id;
    }

    public function setDateTime(\DateTime $date)
    {
        $this->dateTime = $date;
    }

    public function getDateTime()
    {
        return $this->dateTime;
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
