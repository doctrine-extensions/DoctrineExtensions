<?php

namespace Gedmo\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * TranslationEntity annotation for Translatable behavioral extension
 *
 * @Annotation
 * @Target("CLASS")
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Mapping.Annotation
 * @subpackage TranslationEntity
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class TranslationEntity extends Annotation
{
    /** @var string @required */
    public $class;
}

