<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\SoftDeleteable\Traits;

use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * A soft deletable trait you can apply to your Doctrine ORM entities.
 * Includes default annotation mapping.
 *
 * @author Wesley van Opdorp <wesley.van.opdorp@freshheads.com>
 */
trait SoftDeleteableEntity
{
    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({
     *     "gedmo.doctrine_extentions.trait.soft_deleteable_entity",
     *     "gedmo.doctrine_extentions.trait.soft_deleteable",
     *     "gedmo.doctrine_extentions.traits",
     * })
     *
     * @var DateTime|null
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(["gedmo.doctrine_extentions.trait.soft_deleteable_entity", "gedmo.doctrine_extentions.trait.soft_deleteable", "gedmo.doctrine_extentions.traits"])]
    protected $deletedAt;

    /**
     * Set or clear the deleted at timestamp.
     *
     * @return self
     */
    public function setDeletedAt(DateTime $deletedAt = null)
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * Get the deleted at timestamp value. Will return null if
     * the entity has not been soft deleted.
     *
     * @return DateTime|null
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * Check if the entity has been soft deleted.
     *
     * @Groups({
     *     "gedmo.doctrine_extentions.trait.soft_deleteable_entity",
     *     "gedmo.doctrine_extentions.trait.soft_deleteable",
     *     "gedmo.doctrine_extentions.traits",
     * })
     *
     * @return bool
     */
    #[Groups(["gedmo.doctrine_extentions.trait.soft_deleteable_entity", "gedmo.doctrine_extentions.trait.soft_deleteable", "gedmo.doctrine_extentions.traits"])]
    public function isDeleted()
    {
        return null !== $this->deletedAt;
    }
}
