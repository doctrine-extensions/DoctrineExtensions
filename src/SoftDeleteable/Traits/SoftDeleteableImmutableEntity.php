<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\SoftDeleteable\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Trait for soft-deletable objects using immutable date/time values.
 *
 * This implementation provides a mapping configuration for the Doctrine ORM.
 */
trait SoftDeleteableImmutableEntity
{
    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     *
     * @var \DateTimeImmutable|null
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected $deletedAt;

    /**
     * Set or clear the deleted at timestamp.
     *
     * @return self
     */
    public function setDeletedAt(?\DateTimeImmutable $deletedAt = null)
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * Get the deleted at timestamp value. Will return null if
     * the entity has not been soft deleted.
     *
     * @return \DateTimeImmutable|null
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * Check if the entity has been soft deleted.
     *
     * @return bool
     */
    public function isDeleted()
    {
        return null !== $this->deletedAt;
    }
}
