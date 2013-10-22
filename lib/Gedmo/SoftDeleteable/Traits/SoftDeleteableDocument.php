<?php

namespace Gedmo\SoftDeleteable\Traits;

/**
 * SoftDeletable Trait, usable with PHP >= 5.4
 *
 * @author Wesley van Opdorp <wesley.van.opdorp@freshheads.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
trait SoftDeleteableDocument
{
    /**
     * @ODM\Date
     */
    protected $deletedAt;

    /**
     * Sets deletedAt.
     *
     * @param \Datetime|null $deletedAt
     *
     * @return $this
     */
    public function setDeletedAt(\DateTime $deletedAt = null)
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * Returns deletedAt.
     *
     * @return \DateTime
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * Is deleted?
     *
     * @return bool
     */
    public function isDeleted()
    {
        return null !== $this->deletedAt;
    }
}
