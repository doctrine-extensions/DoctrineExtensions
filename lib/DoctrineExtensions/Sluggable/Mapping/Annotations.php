<?php

namespace DoctrineExtensions\Sluggable\Mapping;

use Doctrine\Common\Annotations\Annotation;

final class Sluggable extends Annotation {}
final class Slug extends Annotation {
    public $updatable = true;
    public $style = 'default'; // or "camel"
    public $unique = true;
    public $separator = '-';
}