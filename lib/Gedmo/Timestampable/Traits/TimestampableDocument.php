<?php

namespace Gedmo\Timestampable\Traits;

/**
 * Timestampable Trait, usable with PHP >= 5.4
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
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
     * @param  Datetime $createdAt
     * @return $this
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
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
     * @param  DateTime $updatedAt
     * @return $this
     */
    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
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
