<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Uploadable\Mapping\Driver;

use Gedmo\Mapping\Driver\AnnotationDriverInterface;

/**
 * Mapping driver for the uploaded extension which reads extended metadata from annotations on an uploadable class.
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @deprecated since gedmo/doctrine-extensions 3.16, will be removed in version 4.0.
 *
 * @internal
 */
class Annotation extends Attribute implements AnnotationDriverInterface
{
}
