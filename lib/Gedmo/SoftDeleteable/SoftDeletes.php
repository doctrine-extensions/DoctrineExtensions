<?php

namespace Gedmo\SoftDeleteable;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

trait SoftDeletes
{
    /**
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     * @var DateTime
     */
    protected $deletedAt;

    /**
     * @return DateTime
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * @param DateTime|null $deletedAt
     */
    public function setDeletedAt(DateTime $deletedAt = null)
    {
        $this->deletedAt = $deletedAt;
    }

    /**
     * Restore the soft-deleted state
     */
    public function restore()
    {
        $this->deletedAt = null;
    }

    /**
     * @return bool
     */
    public function isDeleted()
    {
        return $this->deletedAt && new DateTime('now') >= $this->deletedAt;
    }
}
