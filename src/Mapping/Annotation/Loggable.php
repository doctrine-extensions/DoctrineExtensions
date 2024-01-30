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
use Gedmo\Loggable\LogEntryInterface;
use Gedmo\Mapping\Annotation\Annotation as GedmoAnnotation;

/**
 * Loggable annotation for Loggable behavioral extension
 *
 * @phpstan-template T of LogEntryInterface
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
final class Loggable implements GedmoAnnotation
{
    use ForwardCompatibilityTrait;

    /**
     * @var string|null
     *
     * @phpstan-var class-string<T>|null
     */
    public $logEntryClass;

    /**
     * @param array<string, mixed> $data
     *
     * @phpstan-param class-string<T>|null $logEntryClass
     */
    public function __construct(array $data = [], ?string $logEntryClass = null)
    {
        if ([] !== $data) {
            Deprecation::trigger(
                'gedmo/doctrine-extensions',
                'https://github.com/doctrine-extensions/DoctrineExtensions/pull/2357',
                'Passing an array as first argument to "%s()" is deprecated. Use named arguments instead.',
                __METHOD__
            );

            $args = func_get_args();

            $this->logEntryClass = $this->getAttributeValue($data, 'logEntryClass', $args, 1, $logEntryClass);

            return;
        }

        $this->logEntryClass = $logEntryClass;
    }
}
