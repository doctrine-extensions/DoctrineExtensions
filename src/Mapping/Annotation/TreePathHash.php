<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Mapping\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation;
use Gedmo\Mapping\Annotation\Annotation as GedmoAnnotation;

/**
 * TreePath annotation for Tree behavioral extension
 *
 * @Annotation
 * @Target("PROPERTY")
 *
 * @author <rocco@roccosportal.com>
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class TreePathHash implements GedmoAnnotation
{
}
