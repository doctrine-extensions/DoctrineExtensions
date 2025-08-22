<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Sortable;

/**
 * Marker interface for objects which can be identified as sortable.
 *
 * @author Lukas Botsch <lukas.botsch@gmail.com>
 */
interface Sortable
{
    // use now annotations instead of predefined methods, this interface is not necessary

    /*
     * @Gedmo\SortablePosition - to mark property which will hold the item position use annotation @Gedmo\SortablePosition
     *              This property has to be numeric. The position index can be negative and will be counted from right to left.
     *
     * example:
     *
     * @Gedmo\SortablePosition
     * @Column(type="int")
     * $position
     *
     * @Gedmo\SortableGroup
     * @Column(type="string", length=64)
     * $category
     *
     */

    /*
     * @Gedmo\SortableGroup - to group node sorting by a property use annotation @Gedmo\SortableGroup on this property
     *
     * example:
     *
     * @Gedmo\SortableGroup
     * @Column(type="string", length=64)
     * $category
     */
}
