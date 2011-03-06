<?php

namespace Gedmo\Tree\Entity;

/**
 * @MappedSuperclass
 */
abstract class AbstractClosure
{
    /**
     * @Id
     * @Column(type="integer")
     */
    private $ancestor;
    
    /**
     * @Id
     * @Column(type="integer")
     */
    private $descendant;
    
    /**
     * @Column(type="integer")
     */
    private $depth;
}