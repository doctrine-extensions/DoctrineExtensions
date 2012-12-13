<?php

namespace Gedmo\Blameable;

/**
 * This interface is not necessary but can be implemented for
 * Entities which in some cases needs to be identified as
 * Blameable
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Blameable
 * @subpackage Blameable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface Blameable
{
    // blameable expects annotations on properties

    /**
     * @gedmo:Blameable(on="create")
     * dates which should be updated on insert only
     */

    /**
     * @gedmo:Blameable(on="update")
     * dates which should be updated on update and insert
     */

    /**
     * @gedmo:Blameable(on="change", field="field", value="value")
     * dates which should be updated on changed "property"
     * value and become equal to given "value"
     */

    /**
     * @gedmo:Blameable(on="change", field="field")
     * dates which should be updated on changed "property"
     */

    /**
     * example
     *
     * @gedmo:Blameable(on="create")
     * @Column(type="date")
     * $created
     */
}