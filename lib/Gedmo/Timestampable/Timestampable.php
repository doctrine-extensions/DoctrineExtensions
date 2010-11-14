<?php

namespace Gedmo\Timestampable;

/**
 * This interface is not necessary but can be implemented for
 * Entities which in some cases needs to be identified as
 * Timestampable
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Timestampable
 * @subpackage Timestampable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface Timestampable
{
    // timestampable expects annotations on properties
    
    /**
     * @gedmo:Timestampable(on="create")
     * dates which should be updated on insert only
     */
    
    /**
     * @gedmo:Timestampable(on="update")
     * dates which should be updated on update and insert
     */
    
    /**
     * @gedmo:Timestampable(on="change", field="field", value="value")
     * dates which should be updated on changed "property" 
     * value and become equal to given "value"
     */
    
    /**
     * example
     * 
     * @gedmo:Timestampable(on="create")
     * @Column(type="date")
     * $created
     */
}