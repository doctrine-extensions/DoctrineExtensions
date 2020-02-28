<?php

namespace Gedmo\Tree\Traits;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * NestedSet Trait, usable with PHP >= 5.4
 *
 * @author Renaat De Muynck <renaat.demuynck@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
trait NestedSetEntity
{
    /**
     * @var integer
     * @Gedmo\TreeRoot
     * @ORM\Column(name="root", type="integer", nullable=true)
     */
    private $root;

    /**
     * @var integer
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer")
     */
    private $level;

    /**
     * @var integer
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer")
     */
    private $left;

    /**
     * @var integer
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer")
     */
    private $right;
}
