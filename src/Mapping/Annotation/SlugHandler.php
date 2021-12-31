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
use Gedmo\Sluggable\Handler\SlugHandlerInterface;

/**
 * SlugHandler annotation for Sluggable behavioral extension
 *
 * @Annotation
 * @NamedArgumentConstructor
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final class SlugHandler implements GedmoAnnotation
{
    /**
     * @var string
     * @phpstan-var string|class-string<SlugHandlerInterface>
     */
    public $class = '';

    /**
     * @var array<SlugHandlerOption>|array<array{string, mixed}>
     */
    public $options = [];

    /**
     * @phpstan-param string|class-string<SlugHandlerInterface> $class
     */
    public function __construct(
        array $data = [],
        string $class = '',
        array $options = []
    ) {
        if ([] !== $data) {
            @trigger_error(sprintf(
                'Passing an array as first argument to "%s()" is deprecated. Use named arguments instead.',
                __METHOD__
            ), E_USER_DEPRECATED);
        }

        $this->class = $data['class'] ?? $class;
        $this->options = $data['options'] ?? $options;
    }
}
