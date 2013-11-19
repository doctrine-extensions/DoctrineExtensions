<?php

namespace Gedmo\IpTraceable;

/**
 * This interface is not necessary but can be implemented for
 * Entities which in some cases needs to be identified as
 * IpTraceable
 * 
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface IpTraceable
{
    // ipTraceable expects annotations on properties
    
    /**
     * @gedmo:IpTraceable(on="create")
     * strings which should be updated on insert only
     */
    
    /**
     * @gedmo:IpTraceable(on="update")
     * strings which should be updated on update and insert
     */
    
    /**
     * @gedmo:IpTraceable(on="change", field="field", value="value")
     * strings which should be updated on changed "property" 
     * value and become equal to given "value"
     */

    /**
     * @gedmo:IpTraceable(on="change", field="field")
     * strings which should be updated on changed "property"
     */

    /**
     * @gedmo:IpTraceable(on="change", fields={"field1", "field2"})
     * strings which should be updated if at least one of the given fields changed
     */

    /**
     * example
     * 
     * @gedmo:IpTraceable(on="create")
     * @Column(type="string")
     * $created
     */
}