<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * Tree annotation for Tree behavioral extension
 *
 * @Annotation
 * @Target("CLASS")
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class Tree extends Annotation
{
    /** @var string */
    public $type = 'nested';

    /** @var bool */
    public $activateLocking = false;

    /** @var int */
    public $lockingTimeout = 3;

    /** @var string */
    public $identifierMethod;
}
