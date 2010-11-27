<?php

namespace Tree\Fixture;

/**
 * @MappedSuperclass
 */
class ANode
{
    /** 
     * @Id 
     * @GeneratedValue 
     * @Column(type="integer")
     */
    private $id;
    
    /**
     * @gedmo:TreeLeft
     * @Column(type="integer", nullable=true)
     */
    private $lft;
    
    /**
     * @gedmo:TreeRight
     * @Column(type="integer", nullable=true)
     */
    private $rgt;
    
    public function getId()
    {
        return $this->id;
    }
}