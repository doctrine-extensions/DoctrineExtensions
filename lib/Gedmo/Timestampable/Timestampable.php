<?php

namespace Gedmo\Timestampable;

/**
 * This interface is not necessary but can be implemented for
 * Entities which in some cases needs to be identified as
 * Timestampable
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
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
     * @gedmo:Timestampable(on="change", field="field")
     * dates which should be updated on changed "property"
     */

    /**
     * @gedmo:Timestampable(on="change", fields={"field1", "field2"})
     * dates which should be updated if at least one of the given fields changed
     */

    /**
     * example
     * 
     * @gedmo:Timestampable(on="create")
     * @Column(type="date")
     * $created
     */
}