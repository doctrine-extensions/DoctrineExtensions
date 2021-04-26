<?php

namespace Gedmo\Tree\Traits;

/**
 * NestedSet Trait, usable with PHP >= 5.4
 *
 * @author Renaat De Muynck <renaat.demuynck@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
trait NestedSet
{
    /**
     * @var int
     */
    private $root;

    /**
     * @var int
     */
    private $level;

    /**
     * @var int
     */
    private $left;

    /**
     * @var int
     */
    private $right;
}
