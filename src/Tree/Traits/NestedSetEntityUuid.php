<?php

namespace Gedmo\Tree\Traits;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * NestedSet Trait with UUid, usable with PHP >= 5.4
 *
 * @author Benjamin Lazarecki <benjamin.lazarecki@sensiolabs.com>
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
trait NestedSetEntityUuid
{
    use NestedSetEntity;

    /**
     * @var string
     * @Gedmo\TreeRoot
     * @ORM\Column(name="root", type="string", nullable=true)
     */
    private $root;
}
