<?php

namespace Gedmo\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * Blameable annotation for Blameable behavioral extension
 *
 * @Annotation
 * @Target("PROPERTY")
 *
 * @author David Buchmann <mail@davidbu.ch>
 * @package Gedmo.Mapping.Annotation
 * @subpackage Blameable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class Blameable extends Annotation
{
    /** @var string */
    public $on = 'update';
    /** @var string */
    public $field;
    /** @var mixed */
    public $value;
}

