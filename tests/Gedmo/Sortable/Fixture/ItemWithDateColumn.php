<?php

namespace Gedmo\Tests\Sortable\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sortable\Entity\Repository\SortableRepository;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Sortable\Entity\Repository\SortableRepository")
 */
#[ORM\Entity(repositoryClass: SortableRepository::class)]
class ItemWithDateColumn
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
     * @Gedmo\SortablePosition
     * @ORM\Column(type="integer")
     */
    #[Gedmo\SortablePosition]
    #[ORM\Column(type: 'integer')]
    //private $position = -1;
    private $position = 0;

    /**
     * @Gedmo\SortableGroup
     * @ORM\Column(type="date")
     */
    #[Gedmo\SortableGroup]
    #[ORM\Column(type: 'date')]
    private $date;

    /**
     * @Gedmo\SortableGroup
     * @ORM\Column(nullable=false)
     */
    #[Gedmo\SortableGroup]
    #[ORM\Column(nullable: false)]
    private $userId;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * @param int $position
     */
    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    /**
     * @return mixed
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param mixed $date
     */
    public function setDate($date): void
    {
        $this->date = $date;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param mixed $userId
     */
    public function setUserId($userId): void
    {
        $this->userId = $userId;
    }
}
