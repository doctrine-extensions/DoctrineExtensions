<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Translatable\Mapping\Driver;

use Gedmo\Mapping\Annotation\Translatable;
use Gedmo\Mapping\Driver\AttributeDriverInterface;

/**
 * This is an attribute mapping driver for Translatable
 * behavioral extension. Used for extraction of extended
 * metadata from attributes specifically for Translatable
 * extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @internal
 */
final class Attribute extends Annotation implements AttributeDriverInterface
{
}
