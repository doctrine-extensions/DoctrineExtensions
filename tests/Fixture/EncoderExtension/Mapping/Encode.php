<?php

namespace Fixture\EncoderExtension\Mapping;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Encode extends Annotation
{
    /** @var string */
    public $type = 'md5'; // as default

    /** @var string */
    public $secret = '';
}
