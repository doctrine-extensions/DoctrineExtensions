<?php

namespace Gedmo\Sortable;

/**
 * This interface is not necessary but can be implemented for
 * Entities which in some cases needs to be identified as
 * Sortable
 *
 * @author Lukas Botsch <lukas.botsch@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface Sortable
{
    // use now annotations instead of predefined methods, this interface is not necessary

    /**
     * example:
     *
     * @gedmo:Sortable(groups={"category"})
     * @Column(type="int")
     * $position
     *
     * @Column(type="string", length=64)
     * $category
     *
     */
}
