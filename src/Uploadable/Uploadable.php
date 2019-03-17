<?php

namespace Gedmo\Uploadable;

/**
 * This interface is not necessary but can be implemented for
 * Domain Objects which in some cases needs to be identified as
 * Uploadable
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface Uploadable
{
    // this interface is not necessary to implement

    /**
     * @gedmo:Uploadable
     * to mark the class as Uploadable use class annotation @gedmo:Uploadable
     * this object will be able Uploadable
     * example:
     *
     * @gedmo:Uploadable
     * class MyEntity
     */
}
