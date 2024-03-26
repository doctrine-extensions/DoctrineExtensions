<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\IpTraceable;

/**
 * Marker interface for objects which can be identified as IP traceable.
 *
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 */
interface IpTraceable
{
    // ipTraceable expects annotations on properties

    /*
     * @Gedmo\IpTraceable(on="create")
     * strings which should be updated on insert only
     */

    /*
     * @Gedmo\IpTraceable(on="update")
     * strings which should be updated on update and insert
     */

    /*
     * @Gedmo\IpTraceable(on="change", field="field", value="value")
     * strings which should be updated on changed "property"
     * value and become equal to given "value"
     */

    /*
     * @Gedmo\IpTraceable(on="change", field="field")
     * strings which should be updated on changed "property"
     */

    /*
     * @Gedmo\IpTraceable(on="change", fields={"field1", "field2"})
     * strings which should be updated if at least one of the given fields changed
     */

    /*
     * example
     *
     * @Gedmo\IpTraceable(on="create")
     * @Column(type="string")
     * $created
     */
}
