<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Sortable;

/**
 * This interface is not necessary but can be implemented for
 * Entities which in some cases needs to be identified as
 * Sortable
 *
 * @author Lukas Botsch <lukas.botsch@gmail.com>
 */
interface Sortable
{
    // use now annotations instead of predefined methods, this interface is not necessary

    /*
     * @gedmo:SortablePosition - to mark property which will hold the item position use annotation @gedmo:SortablePosition
     *              This property has to be numeric. The position index can be negative and will be counted from right to left.
     *
     * example:
     *
     * @gedmo:SortablePosition
     * @Column(type="int")
     * $position
     *
     * @gedmo:SortableGroup
     * @Column(type="string", length=64)
     * $category
     *
     */

    /*
     * @gedmo:SortableGroup - to group node sorting by a property use annotation @gedmo:SortableGroup on this property
     *
     * example:
     *
     * @gedmo:SortableGroup
     * @Column(type="string", length=64)
     * $category
     */
}
