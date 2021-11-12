<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tree;

/**
 * This interface is not necessary but can be implemented for
 * Entities which in some cases needs to be identified as
 * Tree Node
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
interface Node
{
    // use now annotations instead of predefined methods, this interface is not necessary

    /*
     * @gedmo:TreeLeft
     * to mark the field as "tree left" use property annotation @gedmo:TreeLeft
     * it will use this field to store tree left value
     */

    /*
     * @gedmo:TreeRight
     * to mark the field as "tree right" use property annotation @gedmo:TreeRight
     * it will use this field to store tree right value
     */

    /*
     * @gedmo:TreeParent
     * in every tree there should be link to parent. To identify a relation
     * as parent relation to child use @Tree:Ancestor annotation on the related property
     */

    /*
     * @gedmo:TreeLevel
     * level of node.
     */
}
