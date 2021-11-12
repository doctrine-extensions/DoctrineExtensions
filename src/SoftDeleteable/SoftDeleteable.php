<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\SoftDeleteable;

/**
 * This interface is not necessary but can be implemented for
 * Domain Objects which in some cases needs to be identified as
 * SoftDeleteable
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
interface SoftDeleteable
{
    // this interface is not necessary to implement

    /*
     * @gedmo:SoftDeleteable
     * to mark the class as SoftDeleteable use class annotation @gedmo:SoftDeleteable
     * this object will be able to be soft deleted
     * example:
     *
     * @gedmo:SoftDeleteable
     * class MyEntity
     */
}
