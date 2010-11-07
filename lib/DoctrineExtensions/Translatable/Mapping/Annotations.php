<?php

namespace DoctrineExtensions\Translatable\Mapping;

use Doctrine\Common\Annotations\Annotation;

final class Translatable extends Annotation {}
final class Locale extends Annotation {}
final class Language extends Annotation {}
final class TranslationEntity extends Annotation {
    public $class;
}