<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Loggable;

/**
 * This interface is not necessary but can be implemented for
 * Domain Objects which in some cases needs to be identified as
 * Loggable
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
interface Loggable
{
    // this interface is not necessary to implement

    /*
     * @gedmo:Loggable
     * to mark the class as loggable use class annotation @gedmo:Loggable
     * this object will contain now a history
     * available options:
     *         logEntryClass="My\LogEntryObject" (optional) defaultly will use internal object class
     * example:
     *
     * @gedmo:Loggable(logEntryClass="My\LogEntryObject")
     * class MyEntity
     */
}
