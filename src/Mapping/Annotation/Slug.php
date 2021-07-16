<?php

namespace Gedmo\Mapping\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation;

/**
 * Slug annotation for Sluggable behavioral extension
 *
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("PROPERTY")
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Slug
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
    public $unique_base = null;
    /** @var string */
    public $separator = '-';
    /** @var string */
    public $prefix = '';
    /** @var string */
    public $suffix = '';
    /** @var array<Gedmo\Mapping\Annotation\SlugHandler> */
    public $handlers = [];
    /** @var string */
    public $dateFormat = 'Y-m-d-H:i';

    /**
     *
     * @param array<string> $fields
     * @param bool $field
     * @param string $style
     * @param bool $unique
     * @param string $unique_base
     * @param string $separator
     * @param string $prefix
     * @param string $suffix
     * @param array<Gedmo\Mapping\Annotation\SlugHandler> $handlers
     * @param string $dateFormat
     *
     * @return void
     */
    public function __construct(
        $fields = [],
        $updatable = true,
        $style = 'default',
        $unique = true,
        $unique_base = null,
        $separator = '-',
        $prefix = '',
        $suffix = '',
        $handlers = [],
        $dateFormat = 'Y-m-d-H:i'
    ) {
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
