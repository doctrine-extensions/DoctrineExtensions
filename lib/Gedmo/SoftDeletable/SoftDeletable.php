<?php

namespace Gedmo\SoftDeletable;

/**
 * This interface is not necessary but can be implemented for
 * Domain Objects which in some cases needs to be identified as
 * SoftDeletable
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface SoftDeletable
{
    // this interface is not necessary to implement
    
    /**
     * @gedmo:SoftDeletable
     * to mark the class as SoftDeletable use class annotation @gedmo:SoftDeletable
     * this object will be able to be soft deleted
     * example:
     * 
     * @gedmo:SoftDeletable
     * class MyEntity
     */
}