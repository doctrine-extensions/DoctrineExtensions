<?php

namespace Gedmo\Mapping\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation;
use Gedmo\Mapping\Annotation\Annotation as GedmoAnnotation;

/**
 * Translatable annotation for Translatable behavioral extension
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("PROPERTY")
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Translatable implements GedmoAnnotation
{
    /** @var bool|null */
    public $fallback;

    public function __construct(?bool $fallback = null)
    {
        $this->fallback = $fallback;
    }
}
