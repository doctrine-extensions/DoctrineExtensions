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
	 * @GeneratedValue(strategy="IDENTITY")
	 */
	private $id;
	
	/**
     * @Column(type="integer")
     */
    private $ancestor;
    
    /**
     * @Column(type="integer")
     */
    private $descendant;
    
    /**
     * @Column(type="integer")
     */
    private $depth;
}