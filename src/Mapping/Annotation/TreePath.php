<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Deprecations\Deprecation;
use Gedmo\Mapping\Annotation\Annotation as GedmoAnnotation;

/**
 * TreePath annotation for Tree behavioral extension
 *
 * @Annotation
 *
 * @NamedArgumentConstructor
 *
 * @Target("PROPERTY")
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author <rocco@roccosportal.com>
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class TreePath implements GedmoAnnotation
{
    use ForwardCompatibilityTrait;

    public string $separator = ',';

    /** @var bool|null */
    public $appendId;

    public bool $startsWithSeparator = false;

    public bool $endsWithSeparator = true;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        array $data = [],
        string $separator = ',',
        ?bool $appendId = null,
        bool $startsWithSeparator = false,
        bool $endsWithSeparator = true
    ) {
        if ([] !== $data) {
            Deprecation::trigger(
                'gedmo/doctrine-extensions',
                'https://github.com/doctrine-extensions/DoctrineExtensions/pull/2388',
                'Passing an array as first argument to "%s()" is deprecated. Use named arguments instead.',
                __METHOD__
            );

            $args = func_get_args();

            $this->separator = $this->getAttributeValue($data, 'separator', $args, 1, $separator);
            $this->appendId = $this->getAttributeValue($data, 'appendId', $args, 2, $appendId);
            $this->startsWithSeparator = $this->getAttributeValue($data, 'startsWithSeparator', $args, 3, $startsWithSeparator);
            $this->endsWithSeparator = $this->getAttributeValue($data, 'endsWithSeparator', $args, 4, $endsWithSeparator);

            return;
        }

        $this->separator = $separator;
        $this->appendId = $appendId;
        $this->startsWithSeparator = $startsWithSeparator;
        $this->endsWithSeparator = $endsWithSeparator;
    }
}
