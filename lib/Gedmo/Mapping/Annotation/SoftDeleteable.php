<?php

namespace Gedmo\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * Group annotation for SoftDeleteable extension
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @package Gedmo.Mapping.Annotation
 * @subpackage SoftDeleteable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 *
 * @Annotation
 * @Target("CLASS")
 */
final class SoftDeleteable extends Annotation
{
    /** @var string */
    public $fieldName = 'deletedAt';
}