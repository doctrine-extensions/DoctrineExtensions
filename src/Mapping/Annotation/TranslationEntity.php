<?php

namespace Gedmo\Mapping\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation;
use Gedmo\Mapping\Annotation\Annotation as GedmoAnnotation;

/**
 * TranslationEntity annotation for Translatable behavioral extension
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("CLASS")
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class TranslationEntity implements GedmoAnnotation
{
    /** @var string @Required */
    public $class;

    public function __construct(string $class)
    {
        $this->class = $class;
    }
}
