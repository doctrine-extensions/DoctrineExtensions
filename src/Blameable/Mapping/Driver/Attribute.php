<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Blameable\Mapping\Driver;

use Gedmo\Mapping\Driver\AttributeDriverInterface;

/**
 * This is an attribute mapping driver for Blameable
 * behavioral extension. Used for extraction of extended
 * metadata from attribute specifically for Blameable
 * extension.
 *
 * @internal
 */
final class Attribute extends Annotation implements AttributeDriverInterface
{
}
