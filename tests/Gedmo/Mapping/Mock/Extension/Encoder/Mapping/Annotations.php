<?php

// file: vendor/Extension/Encoder/Mapping/Annotations.php

namespace Gedmo\Mapping\Mock\Extension\Encoder\Mapping;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
final class Encode extends Annotation
{
    public $type = 'md5';
    public $secret;
}
