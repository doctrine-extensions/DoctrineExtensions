<?php

namespace Gedmo\Translatable\Mapping;

use Doctrine\Common\Annotations\Annotation;

/**
 * These are Translatable extension annotations which should be used
 * on for specific record translation on any Entity
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Translatable.Mapping
 * @subpackage Annotations
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class Translatable extends Annotation {}
final class Locale extends Annotation {}
final class Language extends Annotation {}
final class TranslationEntity extends Annotation {
    public $class;
}