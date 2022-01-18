<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Uploadable\Mapping\Driver;

use Gedmo\Mapping\Annotation\Uploadable;
use Gedmo\Mapping\Driver\AttributeDriverInterface;

/**
 * This is an attribute mapping driver for Uploadable
 * behavioral extension. Used for extraction of extended
 * metadata from attribute specifically for Uploadable
 * extension.
 *
 * @internal
 */
class Attribute extends Annotation implements AttributeDriverInterface
{
}
