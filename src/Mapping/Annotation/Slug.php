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
 * Slug annotation for Sluggable behavioral extension
 *
 * @Annotation
 * @Target("PROPERTY")
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Slug implements GedmoAnnotation
{
    /** @var array<string> @Required */
    public $fields = [];
    /** @var bool */
    public $updatable = true;
    /** @var string */
    public $style = 'default'; // or "camel"
    /** @var bool */
    public $unique = true;
    /** @var string */
    public $unique_base;
    /** @var string */
    public $separator = '-';
    /** @var string */
    public $prefix = '';
    /** @var string */
    public $suffix = '';
    /** @var SlugHandler[] */
    public $handlers = [];
    /** @var string */
    public $dateFormat = 'Y-m-d-H:i';

    /**
     * @phpstan-param class-string|null $type
     */
    public function __construct(array $data = [],
                                array $fields = [],
                                bool $updatable = true,
                                string $style = 'default', // or "camel"
                                bool $unique = true,
                                string $unique_base = '',
                                string $separator = '-',
                                string $prefix = '',
                                string $suffix = '',
                                iterable $handlers = [],
                                string $dateFormat = 'Y-m-d-H:i'
    ) {
        if ([] !== $data) {
            @trigger_error(sprintf(
                'Passing an array as first argument to "%s()" is deprecated. Use named arguments instead.',
                __METHOD__
            ), E_USER_DEPRECATED);
        }

        $this->fields = $data['fields'] ?? $fields;
        $this->updatable = $data['updatable'] ?? $updatable;
        $this->style = $data['style'] ?? $style;
        $this->unique = $data['unique'] ?? $unique;
        $this->unique_base = $data['unique_base'] ?? $unique_base;
        $this->separator = $data['separator'] ?? $separator;
        $this->prefix = $data['prefix'] ?? $prefix;
        $this->suffix = $data['suffix'] ?? $suffix;
        $this->handlers = $data['handlers'] ?? $handlers;
        $this->dateFormat = $data['dateFormat'] ?? $dateFormat;
    }
}
