<?php

namespace DoctrineExtensions\Translatable\Mapping;

use Doctrine\Common\Annotations\Annotation;

/**
 * These are Translatable extension annotations which should be used
 * on for specific record translation on any Entity
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package DoctrineExtensions.Translatable.Mapping
 * @subpackage Annotations
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class Field extends Annotation {}
final class Locale extends Annotation {}
final class Language extends Annotation {}
final class Entity extends Annotation {
    public $class;
}