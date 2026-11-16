<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Timestampable\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Trait for timestampable objects using immutable date/time values.
 *
 * This implementation provides a mapping configuration for the Doctrine ORM.
 */
trait TimestampableImmutableEntity
{
    /**
     * @Gedmo\Timestampable(on="create")
     *
     * @ORM\Column(type="datetime_immutable")
     */
    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected ?\DateTimeImmutable $createdAt = null;

    /**
     * @Gedmo\Timestampable(on="update")
     *
     * @ORM\Column(type="datetime_immutable")
     */
    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected ?\DateTimeImmutable $updatedAt = null;

    /**
     * Sets createdAt.
     *
     * @return $this
     */
    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Returns createdAt.
     */
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Sets updatedAt.
     *
     * @return $this
     */
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Returns updatedAt.
     */
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
