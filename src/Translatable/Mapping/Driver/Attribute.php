<?php

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
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 *
 * @internal
 */
final class Attribute extends Annotation implements AttributeDriverInterface
{
}
