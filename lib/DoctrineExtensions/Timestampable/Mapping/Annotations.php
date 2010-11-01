<?php

namespace DoctrineExtensions\Timestampable\Mapping;

use Doctrine\Common\Annotations\Annotation;

final class OnCreate extends Annotation {}
final class OnUpdate extends Annotation {}
final class OnChange extends Annotation {
    public $field;
    public $value;
}