<?php

namespace Gedmo\Mapping\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * ReferenceIntegrity annotation for ReferenceIntegrity behavioral extension
 *
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("PROPERTY")
 *
 * @author Evert Harmeling <evert.harmeling@freshheads.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class ReferenceIntegrity
{
    /** @var null|mixed */
    public $value;

    /**
     *
     * @param mixed|null $value
     *
     * @return void
     */
    public function __construct($value = null)
    {
        $this->value = $value;
    }
}
