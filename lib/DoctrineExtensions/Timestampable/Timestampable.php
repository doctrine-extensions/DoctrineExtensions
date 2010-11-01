<?php

namespace DoctrineExtensions\Timestampable;

/**
 * This interface must be implemented for all entities
 * to activate the Timestampable behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package DoctrineExtensions.Timestampable
 * @subpackage Timestampable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface Timestampable
{
    // timestampable expects annotations on properties
    
    // Timestampable:OnCreate - dates which should be updated on creation
    // Timestampable:OnUpdate - dates which should be updated on update
    
    /**
     * example
     * 
     * @Timestampable:OnCreate
     * @Column(type="date")
     */
    //$created
}