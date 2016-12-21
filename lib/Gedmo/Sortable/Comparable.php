<?php

namespace Gedmo\Sortable;

/**
 * This interface is not necessary but can be implemented for
 * Entities which has custom compare methods
 * Comparable
 *
 * @link https://wiki.php.net/rfc/comparable
 * @author Raine Ng <yellow1912@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface Comparable
{
    public function compareTo($other);
}
