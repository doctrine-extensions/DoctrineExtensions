<?php

namespace Gedmo\SoftDeleteable\Traits;

/**
 * SoftDeletable Trait, usable with PHP >= 5.4
 *
 * @author Wesley van Opdorp <wesley.van.opdorp@freshheads.com>
 * @package Gedmo.SoftDeleteable.Traits
 * @subpackage SoftDeleteableEntity
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
trait SoftDeleteableEntity
{
    /**
     * @Gedmo\SoftDeleteable(on="delete")
     * @ORM\Column(type="datetime")
     */
    protected $deletedAt;

    /**
     * Sets deletedAt.
     *
     * @param  Datetime $deletedAt
     * @return $this
     */
    public function setDeletedAt(\DateTime $deletedAt)
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
