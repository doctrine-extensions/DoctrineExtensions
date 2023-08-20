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
 * @NamedArgumentConstructor
 * @Target("PROPERTY")
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Slug implements GedmoAnnotation
{
    use ForwardCompatibilityTrait;

    public const STYLE_LOWER = 'lower';
    public const STYLE_UPPER = 'upper';
    public const STYLE_CAMEL = 'camel';
    public const STYLE_ORIGINAL = 'default';
    public const DATE_FORMAT_DEFAULT = 'Y-m-d-H:i';

    /** @var string[] @Required */
    public $fields = [];
    /** @var bool */
    public $updatable = true;
    /**
     * @var string
     *
     * @phpstan-var self::STYLE_*
     */
    public $style = self::STYLE_ORIGINAL;
    /** @var bool */
    public $unique = true;
    /** @var string|null */
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
    public $dateFormat = self::DATE_FORMAT_DEFAULT;

    /**
     * @param string[]      $fields
     * @param SlugHandler[] $handlers
     *
     * @phpstan-param self::STYLE_* $style
     */
    public function __construct(
        array $data = [],
        array $fields = [],
        bool $updatable = true,
        string $style = self::STYLE_ORIGINAL,
        bool $unique = true,
        ?string $unique_base = null,
        string $separator = '-',
        string $prefix = '',
        string $suffix = '',
        array $handlers = [],
        string $dateFormat = self::DATE_FORMAT_DEFAULT
    ) {
        if ([] !== $data) {
            @trigger_error(sprintf(
                'Passing an array as first argument to "%s()" is deprecated. Use named arguments instead.',
                __METHOD__
            ), E_USER_DEPRECATED);

            $args = func_get_args();

            $this->fields = $this->getAttributeValue($data, 'fields', $args, 1, $fields);
            $this->updatable = $this->getAttributeValue($data, 'updatable', $args, 2, $updatable);
            $this->style = $this->getAttributeValue($data, 'style', $args, 3, $style);
            $this->unique = $this->getAttributeValue($data, 'unique', $args, 4, $unique);
            $this->unique_base = $this->getAttributeValue($data, 'unique_base', $args, 5, $unique_base);
            $this->separator = $this->getAttributeValue($data, 'separator', $args, 6, $separator);
            $this->prefix = $this->getAttributeValue($data, 'prefix', $args, 7, $prefix);
            $this->suffix = $this->getAttributeValue($data, 'suffix', $args, 8, $suffix);
            $this->handlers = $this->getAttributeValue($data, 'handlers', $args, 9, $handlers);
            $this->dateFormat = $this->getAttributeValue($data, 'dateFormat', $args, 10, $dateFormat);

            return;
        }

        $this->fields = $fields;
        $this->updatable = $updatable;
        $this->style = $style;
        $this->unique = $unique;
        $this->unique_base = $unique_base;
        $this->separator = $separator;
        $this->prefix = $prefix;
        $this->suffix = $suffix;
        $this->handlers = $handlers;
        $this->dateFormat = $dateFormat;
    }
}
