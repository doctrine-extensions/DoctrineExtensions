<?php

namespace DoctrineExtensions\Timestampable\Mapping;

use Doctrine\Common\Annotations\Annotation;

/**
 * These are Timestampable extension annotations which should be used
 * for date field updates on creation, modification or even on property change
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package DoctrineExtensions.Timestampable.Mapping
 * @subpackage Annotations
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class OnCreate extends Annotation {}
final class OnUpdate extends Annotation {}
final class OnChange extends Annotation {
    public $field;
    public $value;
}