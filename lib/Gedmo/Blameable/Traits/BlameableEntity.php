<?php

namespace Gedmo\Blameable\Traits;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Blameable Trait, usable with PHP >= 5.4
 *
 * @author David Buchmann <mail@davidbu.ch>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
trait BlameableEntity
{
    /**
     * @var string
     * @Gedmo\Blameable(on="create")
     * @ORM\Column(name="created_by", nullable=true)
     */
    protected $createdBy;

    /**
     * @var string
     * @Gedmo\Blameable(on="update")
     * @ORM\Column(name="updated_by", nullable=true)
     */
    protected $updatedBy;

    /**
     * Sets createdBy.
     *
     * @param  string $createdBy
     * @return $this
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Returns createdBy.
     *
     * @return string
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Sets updatedBy.
     *
     * @param  string $updatedBy
     * @return $this
     */
    public function setUpdatedBy($updatedBy)
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    /**
     * Returns updatedBy.
     *
     * @return string
     */
    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }
}
