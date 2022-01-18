<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tree\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * NestedSet Trait with UUid, usable with PHP >= 5.4
 *
 * @author Benjamin Lazarecki <benjamin.lazarecki@sensiolabs.com>
 */
trait NestedSetEntityUuid
{
    use NestedSetEntity;

    /**
     * @var string
     * @Gedmo\TreeRoot
     * @ORM\Column(name="root", type="string", nullable=true)
     */
    #[ORM\Column(name: 'root', type: Types::STRING, nullable: true)]
    #[Gedmo\TreeRoot]
    private $root;
}
