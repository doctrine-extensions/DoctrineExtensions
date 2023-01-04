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
use Gedmo\Loggable\LogEntryInterface;
use Gedmo\Mapping\Annotation\Annotation as GedmoAnnotation;

/**
 * Loggable annotation for Loggable behavioral extension
 *
 * @phpstan-template T of LogEntryInterface
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("CLASS")
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Loggable implements GedmoAnnotation
{
    /**
     * @var string|null
     *
     * @phpstan-var class-string<T>|null
     */
    public $logEntryClass;

    /**
     * @phpstan-param class-string<T>|null $logEntryClass
     */
    public function __construct(array $data = [], ?string $logEntryClass = null)
    {
        if ([] !== $data) {
            @trigger_error(sprintf(
                'Passing an array as first argument to "%s()" is deprecated. Use named arguments instead.',
                __METHOD__
            ), E_USER_DEPRECATED);
        }

        $this->logEntryClass = $data['logEntryClass'] ?? $logEntryClass;
    }
}
