<?php

namespace Gedmo\Mapping\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Blameable annotation for Blameable behavioral extension
 *
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("PROPERTY")
 *
 * @author David Buchmann <mail@davidbu.ch>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Blameable
{
    /** @var string */
    public $on = 'update';
    /** @var string|array */
    public $field;
    /** @var mixed */
    public $value;

    /**
     *
     * @param string $on
     * @param null|string|array $field
     * @param mixed|null $value
     *
     * @return void
     */
    public function __construct($on = 'update', $field = null, $value = null)
    {
        $this->on = $on;
        $this->field = $field;
        $this->value = $value;
    }
}
