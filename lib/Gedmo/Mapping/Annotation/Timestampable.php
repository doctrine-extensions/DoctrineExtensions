<?php

namespace Gedmo\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * Timestampable annotation for Timestampable behavioral extension
 *
 * @Annotation
 * @Target("PROPERTY")
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Mapping.Annotation
 * @subpackage Timestampable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class Timestampable extends Annotation
{
    /** @var string */
    public $on = 'update';
    /** @var string */
    public $field;
    /** @var mixed */
    public $value;
}

