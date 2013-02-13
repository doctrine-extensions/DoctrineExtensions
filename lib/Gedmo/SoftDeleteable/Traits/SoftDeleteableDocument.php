<?php

namespace Gedmo\SoftDeleteable\Traits;

/**
 * SoftDeletable Trait, usable with PHP >= 5.4
 *
 * @author Wesley van Opdorp <wesley.van.opdorp@freshheads.com>
 * @package Gedmo.SoftDeleteable.Traits
 * @subpackage SoftDeleteableDocument
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
trait SoftDeleteableDocument
{
    /**
     * @Gedmo\SoftDeleteable(on="delete")
     * @ODM\Date
     */
    protected $deletedAt;

    /**
     * Sets deletedAt.
     *
     * @param \Datetime|null $deletedAt
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
     * @return DateTime
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }
}
