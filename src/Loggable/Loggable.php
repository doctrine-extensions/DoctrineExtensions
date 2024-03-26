<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Loggable;

/**
 * Marker interface for objects which can be identified as loggable.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
interface Loggable
{
    // this interface is not necessary to implement

    /*
     * @Gedmo\Loggable
     * to mark the class as loggable use class annotation @Gedmo\Loggable
     * this object will contain now a history
     * available options:
     *         logEntryClass="My\LogEntryObject" (optional) defaultly will use internal object class
     * example:
     *
     * @Gedmo\Loggable(logEntryClass="My\LogEntryObject")
     * class MyEntity
     */
}
