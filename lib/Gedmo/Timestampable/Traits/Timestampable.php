<?php

namespace Gedmo\Timestampable\Traits;

/**
 * Timestampable Trait, usable with PHP >= 5.4
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
trait Timestampable
{
    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @var \DateTime
     */
    protected $updatedAt;

    /**
     * Sets createdAt.
     *
     * @param  \DateTimeInterface $createdAt
     * @return $this
     * @throws \Exception
     */
    public function setCreatedAt(\DateTimeInterface $createdAt)
    {
        if ($createdAt instanceof \DateTimeImmutable) {
            $temp = new \DateTime(null, $createdAt->getTimezone());
            $temp->setTimestamp($createdAt->getTimestamp());
            $createdAt = clone $temp;
        }

        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Returns createdAt.
     *
     * @param  bool $mutable
     * @return \DateTimeInterface
     */
    public function getCreatedAt($mutable = true)
    {
        if (!$mutable) {
            return \DateTimeImmutable::createFromMutable($this->createdAt);
        }

        return $this->createdAt;
    }

    /**
     * Sets updatedAt.
     *
     * @param  \DateTimeInterface $updatedAt
     * @return $this
     * @throws \Exception
     */
    public function setUpdatedAt(\DateTimeInterface $updatedAt)
    {
        if ($updatedAt instanceof \DateTimeImmutable) {
            $temp = new \DateTime(null, $updatedAt->getTimezone());
            $temp->setTimestamp($updatedAt->getTimestamp());
            $updatedAt = clone $temp;
        }

        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Returns updatedAt.
     *
     * @param  bool $mutable
     * @return \DateTimeInterface
     */
    public function getUpdatedAt($mutable = true)
    {
        if (!$mutable) {
            return \DateTimeImmutable::createFromMutable($this->updatedAt);
        }

        return $this->updatedAt;
    }
}
