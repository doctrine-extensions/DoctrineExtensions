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
use Gedmo\Mapping\Annotation\Annotation as GedmoAnnotation;

/**
 * IpTraceable annotation for IpTraceable behavioral extension
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("PROPERTY")
 *
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class IpTraceable implements GedmoAnnotation
{
    /** @var string */
    public $on = 'update';
    /** @var string|string[]|null */
    public $field;
    /** @var mixed */
    public $value;

    /**
     * @param string|string[]|null $field
     * @param mixed                $value
     */
    public function __construct(array $data = [], string $on = 'update', $field = null, $value = null)
    {
        if ([] !== $data) {
            @trigger_error(sprintf(
                'Passing an array as first argument to "%s()" is deprecated. Use named arguments instead.',
                __METHOD__
            ), E_USER_DEPRECATED);
        }

        $this->on = $data['on'] ?? $on;
        $this->field = $data['field'] ?? $field;
        $this->value = $data['value'] ?? $value;
    }
}
