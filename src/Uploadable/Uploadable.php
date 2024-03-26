<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Uploadable;

/**
 * Marker interface for objects which can be identified as uploadable.
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
interface Uploadable
{
    // this interface is not necessary to implement

    /*
     * @Gedmo\Uploadable
     * to mark the class as Uploadable use class annotation @Gedmo\Uploadable
     * this object will be able Uploadable
     * example:
     *
     * @Gedmo\Uploadable
     * class MyEntity
     */
}
