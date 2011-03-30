<?php

namespace Mapping\Fixture\Yaml;

/**
 * @MappedSupperClass
 */
class BaseCategory
{
    /**
     * @var integer $lft
     *
     * @Column(name="lft", type="integer")
     */
    private $lft;

    /**
     * @var integer $rgt
     *
     * @Column(name="rgt", type="integer")
     */
    private $rgt;

    /**
     * @var integer $lvl
     *
     * @Column(name="lvl", type="integer")
     */
    private $lvl;
    
    /**
     * @var integer $root
     *
     * @Column(name="root", type="integer")
     */
    private $root;

    /**
     * @var datetime $created
     *
     * @Column(name="created", type="datetime")
     */
    private $created;

    /**
     * @var date $updated
     *
     * @Column(name="updated", type="date")
     */
    private $updated;

    /**
     * Set lft
     *
     * @param integer $lft
     */
    public function setLft($lft)
    {
        $this->lft = $lft;
    }

    /**
     * Get lft
     *
     * @return integer $lft
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * Set rgt
     *
     * @param integer $rgt
     */
    public function setRgt($rgt)
    {
        $this->rgt = $rgt;
    }

    /**
     * Get rgt
     *
     * @return integer $rgt
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * Set lvl
     *
     * @param integer $lvl
     */
    public function setLvl($lvl)
    {
        $this->lvl = $lvl;
    }

    /**
     * Get lvl
     *
     * @return integer $lvl
     */
    public function getLvl()
    {
        return $this->lvl;
    }

    /**
     * Set created
     *
     * @param dateTime $created
     */
    public function setCreated(\dateTime $created)
    {
        $this->created = $created;
    }

    /**
     * Get created
     *
     * @return dateTime $created
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param date $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

    /**
     * Get updated
     *
     * @return date $updated
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}