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
     * @var \DateTimeImmutable|null
     *
     * @Gedmo\Timestampable(on="create")
     *
     * @ORM\Column(type="datetime_immutable")
     */
    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected $createdAt;

    /**
     * @var \DateTimeImmutable|null
     *
     * @Gedmo\Timestampable(on="update")
     *
     * @ORM\Column(type="datetime_immutable")
     */
    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected $updatedAt;

    /**
     * Sets createdAt.
     *
     * @return $this
     */
    public function setCreatedAt(\DateTimeImmutable $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Returns createdAt.
     *
     * @return \DateTimeImmutable|null
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Sets updatedAt.
     *
     * @return $this
     */
    public function setUpdatedAt(\DateTimeImmutable $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Returns updatedAt.
     *
     * @return \DateTimeImmutable|null
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}
