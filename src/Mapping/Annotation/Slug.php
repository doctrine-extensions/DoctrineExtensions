<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * Slug annotation for Sluggable behavioral extension
 *
 * @Annotation
 * @Target("PROPERTY")
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class Slug extends Annotation
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
}
