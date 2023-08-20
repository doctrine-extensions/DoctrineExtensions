<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Mapping\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation;

/**
 * IpTraceable annotation for IpTraceable behavioral extension
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("PROPERTY")
 *
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class IpTraceable implements TrackingAwareAnnotationInterface
{
    use ForwardCompatibilityTrait;

    /**
     * @var string
     *
     * @phpstan-var self::EVENT_*
     */
    public $on = self::EVENT_UPDATE;
    /** @var string|string[]|null */
    public $field;
    /** @var mixed */
    public $value;

    /**
     * @param string|string[]|null $field
     * @param mixed                $value
     *
     * @phpstan-param self::EVENT_* $on
     */
    public function __construct(array $data = [], string $on = self::EVENT_UPDATE, $field = null, $value = null)
    {
        if ([] !== $data) {
            @trigger_error(sprintf(
                'Passing an array as first argument to "%s()" is deprecated. Use named arguments instead.',
                __METHOD__
            ), E_USER_DEPRECATED);

            $args = func_get_args();

            $this->on = $this->getAttributeValue($data, 'on', $args, 1, $on);
            $this->field = $this->getAttributeValue($data, 'field', $args, 2, $field);
            $this->value = $this->getAttributeValue($data, 'value', $args, 3, $value);

            return;
        }

        $this->on = $on;
        $this->field = $field;
        $this->value = $value;
    }
}
