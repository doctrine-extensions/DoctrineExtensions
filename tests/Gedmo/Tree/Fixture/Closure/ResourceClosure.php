<?php

namespace Tree\Fixture\Closure;

use Gedmo\Tree\Entity\MappedSuperclass\AbstractClosure;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class ResourceClosure
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Tree\Fixture\Closure\Resource", fetch="LAZY")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=false)
     * @var integer
     */
    private $ancestor;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Tree\Fixture\Closure\Resource", fetch="LAZY")
     * @ORM\JoinColumn(name="item_id", referencedColumnName="id", nullable=false)
     * @var integer
     */
    private $descendant;

    /**
     * @ORM\Column(name="deep", type="integer")
     * @var integer
     */
    private $depth;

    /**
     * Set ancestor
     *
     * @param object $ancestor
     * @return CategoryClosure
     */
    public function setAncestor($ancestor)
    {
        $this->ancestor = $ancestor;
        return $this;
    }

    /**
     * Get ancestor
     *
     * @return object
     */
    public function getAncestor()
    {
        return $this->ancestor;
    }

    /**
     * Set descendant
     *
     * @param object $descendant
     * @return CategoryClosure
     */
    public function setDescendant($descendant)
    {
        $this->descendant = $descendant;
        return $this;
    }

    /**
     * Get descendant
     *
     * @return object
     */
    public function getDescendant()
    {
        return $this->descendant;
    }

    /**
     * Set depth
     *
     * @param integer $depth
     * @return CategoryClosure
     */
    public function setDepth($depth)
    {
        $this->depth = $depth;
        return $this;
    }

    /**
     * Get depth
     *
     * @return integer
     */
    public function getDepth()
    {
        return $this->depth;
    }
}