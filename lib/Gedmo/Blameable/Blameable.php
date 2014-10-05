<?php

namespace Gedmo\Blameable;

/**
 * This interface is not necessary but can be implemented for
 * Entities which in some cases needs to be identified as
 * Blameable
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface Blameable
{
    // blameable expects annotations on properties

    /**
     * @gedmo:Blameable(on="create")
     * fields which should be updated on insert only
     */

    /**
     * @gedmo:Blameable(on="update")
     * fields which should be updated on update and insert
     */

    /**
     * @gedmo:Blameable(on="change", field="field", value="value")
     * fields which should be updated on changed "property"
     * value and become equal to given "value"
     */

    /**
     * @gedmo:Blameable(on="change", field="field")
     * fields which should be updated on changed "property"
     */

    /**
     * @gedmo:Blameable(on="change", fields={"field1", "field2"})
     * fields which should be updated if at least one of the given fields changed
     */

    /**
     * example
     *
     * @gedmo:Blameable(on="create")
     * @Column(type="string")
     * $created
     */
}
