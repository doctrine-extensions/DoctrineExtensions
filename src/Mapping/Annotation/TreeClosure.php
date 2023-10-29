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
use Gedmo\Tree\Entity\MappedSuperclass\AbstractClosure;

/**
 * TreeClosure annotation for Tree behavioral extension
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
final class TreeClosure implements GedmoAnnotation
{
    use ForwardCompatibilityTrait;

    /**
     * @phpstan-var string|class-string<AbstractClosure>
     */
    public string $class;

    /**
     * @param array<string, mixed> $data
     *
     * @phpstan-param string|class-string<AbstractClosure> $class
     */
    public function __construct(array $data = [], string $class = '')
    {
        if ([] !== $data) {
            @trigger_error(sprintf(
                'Passing an array as first argument to "%s()" is deprecated. Use named arguments instead.',
                __METHOD__
            ), E_USER_DEPRECATED);

            $args = func_get_args();

            $this->class = $this->getAttributeValue($data, 'class', $args, 1, $class);

            return;
        }

        $this->class = $class;
    }
}
