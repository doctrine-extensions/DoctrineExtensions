<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tree\Traits;

/**
 * Trait for objects in a nested tree.
 *
 * This implementation does not provide any mapping configurations.
 *
 * @author Renaat De Muynck <renaat.demuynck@gmail.com>
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
