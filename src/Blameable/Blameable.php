<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Blameable;

/**
 * Marker interface for objects which can be identified as blamable.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
interface Blameable
{
    // blameable expects annotations on properties

    /*
     * @Gedmo\Blameable(on="create")
     * fields which should be updated on insert only
     */

    /*
     * @Gedmo\Blameable(on="update")
     * fields which should be updated on update and insert
     */

    /*
     * @Gedmo\Blameable(on="change", field="field", value="value")
     * fields which should be updated on changed "property"
     * value and become equal to given "value"
     */

    /*
     * @Gedmo\Blameable(on="change", field="field")
     * fields which should be updated on changed "property"
     */

    /*
     * @Gedmo\Blameable(on="change", fields={"field1", "field2"})
     * fields which should be updated if at least one of the given fields changed
     */

    /*
     * example
     *
     * @Gedmo\Blameable(on="create")
     * @Column(type="string")
     * $created
     */
}
