<?php

namespace Gedmo\SoftDeleteable;

/**
 * This interface is not necessary but can be implemented for
 * Domain Objects which in some cases needs to be identified as
 * SoftDeleteable
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface SoftDeleteable
{
    // this interface is not necessary to implement

    /**
     * @gedmo:SoftDeleteable
     * to mark the class as SoftDeleteable use class annotation @gedmo:SoftDeleteable
     * this object will be able to be soft deleted
     * example:
     *
     * @gedmo:SoftDeleteable
     * class MyEntity
     */
}
