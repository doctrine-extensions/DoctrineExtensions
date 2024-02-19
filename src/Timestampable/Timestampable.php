<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Timestampable;

/**
 * Marker interface for objects which can be identified as timestampable.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
interface Timestampable
{
    // timestampable expects annotations on properties

    /*
     * @Gedmo\Timestampable(on="create")
     * dates which should be updated on insert only
     */

    /*
     * @Gedmo\Timestampable(on="update")
     * dates which should be updated on update and insert
     */

    /*
     * @Gedmo\Timestampable(on="change", field="field", value="value")
     * dates which should be updated on changed "property"
     * value and become equal to given "value"
     */

    /*
     * @Gedmo\Timestampable(on="change", field="field")
     * dates which should be updated on changed "property"
     */

    /*
     * @Gedmo\Timestampable(on="change", fields={"field1", "field2"})
     * dates which should be updated if at least one of the given fields changed
     */

    /*
     * example
     *
     * @Gedmo\Timestampable(on="create")
     * @Column(type="date")
     * $created
     */
}
