<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;
use Gedmo\Mapping\Annotation\Annotation as GedmoAnnotation;

/**
 * Tree annotation for Tree behavioral extension
 *
 * @Annotation
 *
 * @NamedArgumentConstructor
 *
 * @Target("CLASS")
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class Tree implements GedmoAnnotation
{
    use ForwardCompatibilityTrait;

    /**
     * @phpstan-var 'closure'|'materializedPath'|'nested'
     */
    public string $type = 'nested';

    public bool $activateLocking = false;

    /**
     * @phpstan-var positive-int
     */
    public int $lockingTimeout = 3;

    /**
     * @var string|null
     *
     * @deprecated to be removed in 4.0, unused, configure the property on the TreeRoot annotation instead
     */
    public $identifierMethod;

    /**
     * @param array<string, mixed> $data
     *
     * @phpstan-param 'closure'|'materializedPath'|'nested'|null $type
     */
    public function __construct(
        array $data = [],
        ?string $type = null,
        bool $activateLocking = false,
        int $lockingTimeout = 3,
        ?string $identifierMethod = null
    ) {
        if ([] !== $data) {
            @trigger_error(sprintf(
                'Passing an array as first argument to "%s()" is deprecated. Use named arguments instead.',
                __METHOD__
            ), E_USER_DEPRECATED);

            $args = func_get_args();

            $this->type = $this->getAttributeValue($data, 'type', $args, 1, $type);
            $this->activateLocking = $this->getAttributeValue($data, 'activateLocking', $args, 2, $activateLocking);
            $this->lockingTimeout = $this->getAttributeValue($data, 'lockingTimeout', $args, 3, $lockingTimeout);
            $this->identifierMethod = $this->getAttributeValue($data, 'identifierMethod', $args, 4, $identifierMethod);

            return;
        }

        $this->type = $type;
        $this->activateLocking = $activateLocking;
        $this->lockingTimeout = $lockingTimeout;
        $this->identifierMethod = $identifierMethod;
    }
}
