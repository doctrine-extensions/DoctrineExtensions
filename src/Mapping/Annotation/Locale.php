<?php

namespace Gedmo\Mapping\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation;

/**
 * Locale annotation for Translatable behavioral extension
 *
 * @Annotation
 * @Target("PROPERTY")
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Locale
{
}
