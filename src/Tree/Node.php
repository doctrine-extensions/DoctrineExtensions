<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tree;

/**
 * Marker interface for objects which can be identified as a tree node.
 *
 * @method void  setSibling(self $node)
 * @method ?self getSibling()
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
interface Node
{
    // use now annotations instead of predefined methods, this interface is not necessary

    /*
     * @Gedmo\TreeLeft
     * to mark the field as "tree left" use property annotation @Gedmo\TreeLeft
     * it will use this field to store tree left value
     */

    /*
     * @Gedmo\TreeRight
     * to mark the field as "tree right" use property annotation @Gedmo\TreeRight
     * it will use this field to store tree right value
     */

    /*
     * @Gedmo\TreeParent
     * in every tree there should be link to parent. To identify a relation
     * as parent relation to child use @Tree:Ancestor annotation on the related property
     */

    /*
     * @Gedmo\TreeLevel
     * level of node.
     */

    // @todo: In the next major release, remove this line and uncomment the method in the next line.
    // public function setSibling(self $node): void;

    // @todo: In the next major release, remove this line and uncomment the method in the next line.
    // public function getSibling(): ?self;
}
