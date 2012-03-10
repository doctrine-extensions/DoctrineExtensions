<?php

namespace Gedmo\Timestampable\Traits;

/**
 * Timestampable Trait, usable with PHP >= 5.4
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Timestampable.Traits
 * @subpackage TimestampableDocument
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
trait TimestampableDocument
{
    /**
     * @Gedmo\Timestampable(on="create")
     * @ODM\Date
     */
    private $createdAt;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ODM\Date
     */
    private $updatedAt;

    /**
     * Sets createdAt.
     *
     * @param Datetime $createdAt
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Returns createdAt.
     *
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Sets updatedAt.
     *
     * @param DateTime $updatedAt
     */
    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Returns updatedAt.
     *
     * @return Datetime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}
