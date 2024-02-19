<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Blameable\Mapping\Driver;

use Gedmo\Mapping\Driver\AnnotationDriverInterface;

/**
 * Mapping driver for the blamable extension which reads extended metadata from annotations on a blamable class.
 *
 * @deprecated since gedmo/doctrine-extensions 3.16, will be removed in version 4.0.
 *
 * @internal
 */
class Annotation extends Attribute implements AnnotationDriverInterface
{
}
